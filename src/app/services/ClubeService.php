<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\ConnectionFactory;
use App\Core\Authorization;
use App\Models\CicloRepository;
use App\Models\ClubeRepository;
use App\Models\MembroClubeRepository;
use App\Models\TemaClubeRepository;
use App\Models\UsuarioRepository;
use DomainException;
use PDO;
use Throwable;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class ClubeService
{
    private PDO $pdo;
    private ClubeRepository $clubeRepo;
    private MembroClubeRepository $membroRepo;
    private TemaClubeRepository $temaRepo;
    private UsuarioRepository $usuarioRepo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
        $this->clubeRepo = new ClubeRepository($this->pdo);
        $this->membroRepo = new MembroClubeRepository($this->pdo);
        $this->temaRepo = new TemaClubeRepository($this->pdo);
        $this->usuarioRepo = new UsuarioRepository($this->pdo);
    }

    public function criarClube(int $idCriador, array $dados): int
    {
        $idTema = (int) ($dados['id_tema'] ?? 0);
        $tema = $this->temaRepo->buscarPorId($idTema);
        if ($tema === null || (int) $tema['ativo'] !== 1) {
            throw new DomainException('Escolha um tema válido para o clube.');
        }

        $slug = $this->gerarSlugUnico((string) ($dados['nome'] ?? 'clube'));

        // Cria o clube e vincula o criador na mesma transação para não deixar clube sem responsável
        $this->pdo->beginTransaction();
        try {
            $idClube = $this->clubeRepo->criar([
                'id_tema' => $idTema,
                'id_criador' => $idCriador,
                'nome' => $dados['nome'],
                'slug' => $slug,
                'descricao' => $dados['descricao'],
                'regras' => $dados['regras'] ?? null,
                'visibilidade' => $dados['visibilidade'],
                'cor_identidade' => $dados['cor_identidade'],
            ]);

            $this->membroRepo->salvarVinculo($idClube, $idCriador, 'responsavel', 'ativo', null);
            $this->pdo->commit();

            return $idClube;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function editarClube(int $idClube, array $dados): void
    {
        Authorization::exigirResponsavel($idClube);

        $clube = $this->clubeRepo->buscarPorId($idClube);
        if ($clube === null) {
            throw new DomainException('Clube não encontrado.');
        }
        if ($clube['status'] === 'arquivado') {
            throw new DomainException('Clubes arquivados não podem ser editados.');
        }

        $idTema = (int) ($dados['id_tema'] ?? 0);
        $tema = $this->temaRepo->buscarPorId($idTema);
        if ($tema === null || (int) $tema['ativo'] !== 1) {
            throw new DomainException('Escolha um tema válido para o clube.');
        }

        $this->clubeRepo->atualizar($idClube, [
            'id_tema' => $idTema,
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'],
            'regras' => $dados['regras'] ?? null,
            'visibilidade' => $dados['visibilidade'],
            'cor_identidade' => $dados['cor_identidade'],
        ]);
    }

    public function entrarEmClubePublico(int $idClube, int $idUsuario): string
    {
        $clube = $this->clubeRepo->buscarPorId($idClube);
        if ($clube === null || $clube['status'] !== 'ativo' || $clube['visibilidade'] !== 'publico') {
            throw new DomainException('Este clube não está disponível para entrada pública.');
        }

        $vinculo = $this->membroRepo->buscarVinculo($idClube, $idUsuario);
        if ($vinculo !== null && $vinculo['status'] === 'ativo') {
            return 'Você já faz parte deste clube.';
        }

        $this->membroRepo->salvarVinculo($idClube, $idUsuario, 'membro', 'ativo', null);
        return 'Agora você participa do clube "' . $clube['nome'] . '".';
    }

    public function sairDoClube(int $idClube, int $idUsuario): void
    {
        $vinculo = Authorization::exigirMembroAtivo($idClube);
        $idAtor = (int) $vinculo['id_usuario'];

        $this->pdo->beginTransaction();
        try {
            // Trava o clube para impedir saída simultânea dos últimos responsáveis ativos
            $lock = $this->pdo->prepare('SELECT id_clube FROM clubes WHERE id_clube = :id FOR UPDATE');
            $lock->execute([':id' => $idClube]);

            if ($vinculo['papel'] === 'responsavel') {
                $totalResponsaveis = $this->membroRepo->contarResponsaveisAtivos($idClube);
                if ($totalResponsaveis <= 1) {
                    throw new DomainException(
                        'Você é o único responsável por este clube. Caso deseje encerrá-lo, utilize a opção de arquivar o clube.'
                    );
                }
            }

            $this->membroRepo->atualizarStatus($idClube, $idAtor, 'saiu', false);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function convidarPorApelido(int $idClube, int $idResponsavel, string $apelidoInformado): string
    {
        $vinculoResp = Authorization::exigirResponsavel($idClube);
        $idResp = (int) $vinculoResp['id_usuario'];

        $clube = $this->clubeRepo->buscarPorId($idClube);
        if ($clube === null || $clube['status'] !== 'ativo') {
            throw new DomainException('Não é possível enviar convites em um clube arquivado.');
        }

        $apelido = ltrim(trim($apelidoInformado), '@');
        if ($apelido === '') {
            throw new DomainException('Informe o apelido da pessoa que deseja convidar.');
        }

        $alvo = $this->usuarioRepo->buscarPorApelido($apelido);
        if ($alvo === null || ($alvo['status'] ?? '') !== 'ativo') {
            throw new DomainException('Nenhum leitor ativo foi encontrado com o apelido @' . $apelido . '.');
        }

        $idAlvo = (int) $alvo['id_usuario'];
        if ($idAlvo === $idResp) {
            throw new DomainException('Você já participa deste clube como responsável.');
        }

        $vinculo = $this->membroRepo->buscarVinculo($idClube, $idAlvo);
        if ($vinculo !== null && $vinculo['status'] === 'ativo') {
            throw new DomainException('@' . $alvo['apelido'] . ' já faz parte deste clube.');
        }
        if ($vinculo !== null && $vinculo['status'] === 'pendente') {
            throw new DomainException('Já existe um convite pendente para @' . $alvo['apelido'] . '.');
        }

        $this->membroRepo->salvarVinculo($idClube, $idAlvo, 'membro', 'pendente', $idResp);
        return 'Convite enviado para @' . $alvo['apelido'] . '.';
    }

    public function cancelarConvite(int $idClube, int $idAlvo): void
    {
        Authorization::exigirResponsavel($idClube);
        $this->membroRepo->removerConvitePendente($idClube, $idAlvo);
    }

    public function responderConvite(int $idClube, int $idUsuario, string $decisao): string
    {
        if (!in_array($decisao, ['aceitar', 'recusar'], true)) {
            throw new DomainException('Ação de convite inválida.');
        }

        $vinculo = $this->membroRepo->buscarVinculo($idClube, $idUsuario);
        if ($vinculo === null || $vinculo['status'] !== 'pendente') {
            throw new DomainException('Nenhum convite pendente foi encontrado para este clube.');
        }

        $clube = $this->clubeRepo->buscarPorId($idClube);
        if ($clube === null) {
            throw new DomainException('Clube não encontrado.');
        }

        if ($decisao === 'aceitar') {
            if ($clube['status'] !== 'ativo') {
                throw new DomainException('Este clube foi arquivado e não aceita novos membros.');
            }
            $this->membroRepo->atualizarStatus($idClube, $idUsuario, 'ativo', true);
            return 'Convite aceito! Agora você participa de "' . $clube['nome'] . '".';
        }

        $this->membroRepo->atualizarStatus($idClube, $idUsuario, 'recusado', false);
        return 'Convite recusado.';
    }

    // Arquiva o clube e encerra eventual ciclo aberto na mesma transação, preservando o histórico
    public function arquivarClube(int $idClube): void
    {
        Authorization::exigirResponsavel($idClube);

        $clube = $this->clubeRepo->buscarPorId($idClube);
        if ($clube === null) {
            throw new DomainException('Clube não encontrado.');
        }
        if ($clube['status'] === 'arquivado') {
            return;
        }

        $cicloRepo = new CicloRepository($this->pdo);
        $this->pdo->beginTransaction();
        try {
            $this->clubeRepo->arquivar($idClube);
            $cicloRepo->encerrarAbertosDoClube($idClube);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function gerarSlugUnico(string $nome): string
    {
        $base = mb_substr(slugify($nome), 0, 140, 'UTF-8');
        $candidato = $base;
        $sufixo = 2;

        while ($this->clubeRepo->existeSlug($candidato)) {
            // Trunca a base para que o sufixo numérico não ultrapasse o limite da coluna
            $candidato = mb_substr($base, 0, 125, 'UTF-8') . '-' . $sufixo;
            $sufixo++;
        }

        return $candidato;
    }
}
