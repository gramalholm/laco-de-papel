<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\ConnectionFactory;
use App\Core\Authorization;
use App\Models\CicloRepository;
use App\Models\ClubeRepository;
use App\Models\EtapaRepository;
use App\Models\IndicacaoRepository;
use App\Models\LivroRepository;
use App\Models\VotoRepository;
use DomainException;
use PDO;
use Throwable;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class CicloService
{
    private PDO $pdo;
    private CicloRepository $cicloRepo;
    private ClubeRepository $clubeRepo;
    private LivroRepository $livroRepo;
    private IndicacaoRepository $indicacaoRepo;
    private VotoRepository $votoRepo;
    private EtapaRepository $etapaRepo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
        $this->cicloRepo = new CicloRepository($this->pdo);
        $this->clubeRepo = new ClubeRepository($this->pdo);
        $this->livroRepo = new LivroRepository($this->pdo);
        $this->indicacaoRepo = new IndicacaoRepository($this->pdo);
        $this->votoRepo = new VotoRepository($this->pdo);
        $this->etapaRepo = new EtapaRepository($this->pdo);
    }

    public function criarCiclo(int $idClube, int $idResponsavel, array $dados): int
    {
        $vinculo = Authorization::exigirResponsavel($idClube);
        $idAtor = (int) $vinculo['id_usuario'];

        $idLivroEscolhido = (int) ($dados['id_livro_escolhido'] ?? 0);
        if ($idLivroEscolhido > 0 && $this->livroRepo->buscarPorId($idLivroEscolhido) === null) {
            throw new DomainException('O livro escolhido não foi localizado no acervo.');
        }

        $this->pdo->beginTransaction();
        try {
            // Trava a linha do clube pai para impedir dois ciclos abertos simultâneos ou deadlock em gap lock
            $stmtLock = $this->pdo->prepare('SELECT id_clube, status FROM clubes WHERE id_clube = :id FOR UPDATE');
            $stmtLock->execute([':id' => $idClube]);
            $clube = $stmtLock->fetch();
            if (!is_array($clube) || ($clube['status'] ?? '') !== 'ativo') {
                throw new DomainException('Clubes arquivados não podem iniciar novos ciclos.');
            }

            $aberto = $this->cicloRepo->buscarAtivoDoClube($idClube, true);
            if ($aberto !== null) {
                throw new DomainException('Este clube já possui um ciclo aberto. Encerre o ciclo atual antes de abrir outro.');
            }

            $status = $idLivroEscolhido > 0 ? 'em_andamento' : 'votacao';
            $idCiclo = $this->cicloRepo->criar([
                'id_clube' => $idClube,
                'id_livro_escolhido' => $idLivroEscolhido > 0 ? $idLivroEscolhido : null,
                'titulo_ciclo' => $dados['titulo_ciclo'] ?? null,
                'status' => $status,
                'data_inicio' => $status === 'em_andamento' ? date('Y-m-d') : null,
                'data_limite' => $dados['data_limite'] ?? null,
                'criado_por' => $idAtor,
            ]);

            $this->pdo->commit();
            return $idCiclo;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function indicarLivro(int $idCiclo, int $idUsuario, int $idLivro, ?string $justificativa): void
    {
        $ciclo = $this->obterCicloOuFalhar($idCiclo);
        $idClube = (int) $ciclo['id_clube'];
        $vinculo = Authorization::exigirMembroAtivo($idClube);
        $idAtor = (int) $vinculo['id_usuario'];

        if (($ciclo['clube_status'] ?? '') !== 'ativo') {
            throw new DomainException('Este clube está arquivado e não aceita novas indicações.');
        }
        if ($ciclo['status'] !== 'votacao') {
            throw new DomainException('Novas indicações só podem ser feitas enquanto a votação estiver aberta.');
        }
        if ($this->livroRepo->buscarPorId($idLivro) === null) {
            throw new DomainException('Livro não encontrado no acervo.');
        }
        if ($this->indicacaoRepo->existeLivroNoCiclo($idCiclo, $idLivro)) {
            throw new DomainException('Este livro já foi indicado para esta votação.');
        }

        $this->indicacaoRepo->criar($idCiclo, $idClube, $idLivro, $idAtor, $justificativa);
    }

    public function votar(int $idCiclo, int $idUsuario, int $idIndicacao): string
    {
        $ciclo = $this->obterCicloOuFalhar($idCiclo);
        $idClube = (int) $ciclo['id_clube'];
        $vinculo = Authorization::exigirMembroAtivo($idClube);
        $idAtor = (int) $vinculo['id_usuario'];

        if (($ciclo['clube_status'] ?? '') !== 'ativo') {
            throw new DomainException('Este clube está arquivado e não aceita novos votos.');
        }
        if ($ciclo['status'] !== 'votacao') {
            throw new DomainException('A votação deste ciclo já foi encerrada.');
        }

        $indicacao = $this->indicacaoRepo->buscarPorId($idIndicacao);
        if ($indicacao === null || (int) $indicacao['id_ciclo'] !== $idCiclo) {
            throw new DomainException('Indicação inválida para este ciclo.');
        }

        $this->votoRepo->registrarOuTrocar($idCiclo, $idClube, $idIndicacao, $idAtor);
        return 'Voto registrado para "' . $indicacao['livro_titulo'] . '".';
    }

    public function encerrarVotacao(int $idCiclo, ?int $idIndicacaoDesempate = null): string
    {
        $ciclo = $this->obterCicloOuFalhar($idCiclo);
        $idClube = (int) $ciclo['id_clube'];
        Authorization::exigirResponsavel($idClube);

        if (($ciclo['clube_status'] ?? '') !== 'ativo') {
            throw new DomainException('Clubes arquivados não podem encerrar votações.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmtLock = $this->pdo->prepare('SELECT status FROM ciclos_leitura WHERE id_ciclo = :id FOR UPDATE');
            $stmtLock->execute([':id' => $idCiclo]);
            $statusAtual = $stmtLock->fetchColumn();
            if ($statusAtual !== 'votacao') {
                throw new DomainException('Este ciclo não está em fase de votação.');
            }

            $indicacoes = $this->indicacaoRepo->listarComVotos($idCiclo);
            if ($indicacoes === []) {
                throw new DomainException('É necessário ter pelo menos um livro indicado antes de encerrar a votação.');
            }

            // listarComVotos já vem ordenada por votos; o desempate manual permite ao responsável escolher outra indicação
            $vencedora = $indicacoes[0];
            if ($idIndicacaoDesempate !== null && $idIndicacaoDesempate > 0) {
                $encontrada = null;
                foreach ($indicacoes as $ind) {
                    if ((int) $ind['id_indicacao'] === $idIndicacaoDesempate) {
                        $encontrada = $ind;
                        break;
                    }
                }
                if ($encontrada === null) {
                    throw new DomainException('A indicação escolhida para definir o resultado não pertence a este ciclo.');
                }
                $vencedora = $encontrada;
            }

            if (!$this->cicloRepo->iniciarLeitura($idCiclo, (int) $vencedora['id_livro'])) {
                throw new DomainException('Esta votação já foi encerrada.');
            }

            $this->pdo->commit();
            return 'Votação encerrada! A leitura escolhida é "' . $vencedora['livro_titulo'] . '".';
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function salvarEtapa(int $idCiclo, array $dados, ?int $idEtapa = null): void
    {
        $ciclo = $this->obterCicloOuFalhar($idCiclo);
        $idClube = (int) $ciclo['id_clube'];
        Authorization::exigirResponsavel($idClube);

        if (($ciclo['clube_status'] ?? '') !== 'ativo') {
            throw new DomainException('Clubes arquivados não permitem alterar etapas.');
        }
        if (in_array($ciclo['status'], ['encerrado', 'cancelado'], true)) {
            throw new DomainException('Não é possível alterar etapas de um ciclo encerrado.');
        }

        $ordem = (int) ($dados['ordem'] ?? 0);
        if ($ordem <= 0) {
            $ordem = $this->etapaRepo->proximaOrdem($idCiclo);
        }

        if ($this->etapaRepo->existeOrdemNoCiclo($idCiclo, $ordem, $idEtapa)) {
            throw new DomainException('Já existe uma etapa com a ordem ' . $ordem . ' neste ciclo.');
        }

        $payload = [
            'id_ciclo' => $idCiclo,
            'id_clube' => $idClube,
            'ordem' => $ordem,
            'titulo' => $dados['titulo'],
            'pergunta' => $dados['pergunta'] ?? null,
            'percentual_alvo' => (float) $dados['percentual_alvo'],
            'data_limite' => $dados['data_limite'] ?? null,
        ];

        if ($idEtapa !== null && $idEtapa > 0) {
            $etapa = $this->etapaRepo->buscarPorId($idEtapa);
            if ($etapa === null || (int) $etapa['id_ciclo'] !== $idCiclo) {
                throw new DomainException('Etapa não encontrada neste ciclo.');
            }
            $this->etapaRepo->atualizar($idEtapa, $payload);
            return;
        }

        $this->etapaRepo->criar($payload);
    }

    public function salvarEncontro(int $idCiclo, ?string $encontroEm, ?string $local, ?string $url): void
    {
        $ciclo = $this->obterCicloOuFalhar($idCiclo);
        Authorization::exigirResponsavel((int) $ciclo['id_clube']);

        if (($ciclo['clube_status'] ?? '') !== 'ativo') {
            throw new DomainException('Clubes arquivados não permitem alterar encontros.');
        }
        if (in_array($ciclo['status'], ['encerrado', 'cancelado'], true)) {
            throw new DomainException('Não é possível alterar o encontro de um ciclo encerrado.');
        }

        $this->cicloRepo->atualizarEncontro($idCiclo, $encontroEm, $local, $url);
    }

    public function encerrarCiclo(int $idCiclo): void
    {
        $ciclo = $this->obterCicloOuFalhar($idCiclo);
        Authorization::exigirResponsavel((int) $ciclo['id_clube']);

        if (in_array($ciclo['status'], ['encerrado', 'cancelado'], true)) {
            throw new DomainException('Este ciclo já foi encerrado.');
        }

        $this->cicloRepo->encerrar($idCiclo);
    }

    private function obterCicloOuFalhar(int $idCiclo): array
    {
        $ciclo = $this->cicloRepo->buscarPorId($idCiclo);
        if ($ciclo === null) {
            throw new DomainException('Ciclo de leitura não encontrado.');
        }

        return $ciclo;
    }
}
