<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\ConnectionFactory;
use App\Core\Auth;
use App\Core\Authorization;
use App\Models\AnotacaoRepository;
use App\Models\CicloRepository;
use App\Models\EdicaoRepository;
use App\Models\EtapaRepository;
use App\Models\ProgressoRepository;
use DomainException;
use PDO;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class LeituraService
{
    private PDO $pdo;
    private CicloRepository $cicloRepo;
    private EdicaoRepository $edicaoRepo;
    private ProgressoRepository $progressoRepo;
    private EtapaRepository $etapaRepo;
    private AnotacaoRepository $anotacaoRepo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
        $this->cicloRepo = new CicloRepository($this->pdo);
        $this->edicaoRepo = new EdicaoRepository($this->pdo);
        $this->progressoRepo = new ProgressoRepository($this->pdo);
        $this->etapaRepo = new EtapaRepository($this->pdo);
        $this->anotacaoRepo = new AnotacaoRepository($this->pdo);
    }

    // Salva o progresso (Kindle ou físico) e retorna o percentual normalizado de 0 a 100.
    public function salvarProgresso(int $idCiclo, int $idUsuario, array $dados): float
    {
        $ciclo = $this->cicloRepo->buscarPorId($idCiclo);
        if ($ciclo === null) {
            throw new DomainException('Ciclo de leitura não encontrado.');
        }

        $idClube = (int) $ciclo['id_clube'];
        $vinculo = Authorization::exigirMembroAtivo($idClube);
        $idAtor = (int) $vinculo['id_usuario'];

        if (($ciclo['clube_status'] ?? '') !== 'ativo') {
            throw new DomainException('Este clube está arquivado e não aceita atualizações de progresso.');
        }

        if ($ciclo['status'] !== 'em_andamento' || empty($ciclo['id_livro_escolhido'])) {
            throw new DomainException('O progresso só pode ser atualizado enquanto a leitura estiver em andamento.');
        }

        $formato = is_string($dados['formato'] ?? null) ? trim($dados['formato']) : '';
        $statusLeitura = is_string($dados['status_leitura'] ?? null) ? trim($dados['status_leitura']) : 'lendo';
        if (!in_array($statusLeitura, ['lendo', 'pausado', 'concluido'], true)) {
            $statusLeitura = 'lendo';
        }

        if ($formato === 'kindle') {
            $rawPct = $dados['percentual_kindle'] ?? null;
            if (!is_scalar($rawPct)) {
                throw new DomainException('Informe um percentual de Kindle entre 0% e 100%.');
            }
            $strPct = str_replace(',', '.', trim((string) $rawPct));
            if ($strPct === '' || !is_numeric($strPct)) {
                throw new DomainException('Informe um percentual de Kindle válido entre 0% e 100%.');
            }
            $pctFloat = (float) $strPct;
            if (!is_finite($pctFloat) || $pctFloat < 0.0 || $pctFloat > 100.0) {
                throw new DomainException('Informe um percentual de Kindle entre 0% e 100%.');
            }
            $pct = round($pctFloat, 2);

            if ($pct >= 100.0 && $statusLeitura === 'lendo') {
                $statusLeitura = 'concluido';
            }

            $this->progressoRepo->salvar($idCiclo, $idClube, $idAtor, [
                'formato' => 'kindle',
                'id_edicao' => null,
                'pagina_atual' => null,
                'percentual_kindle' => $pct,
                'status_leitura' => $statusLeitura,
            ]);

            return $pct;
        }

        if ($formato === 'fisico') {
            $rawEdicao = $dados['id_edicao'] ?? null;
            $idEdicao = is_scalar($rawEdicao) && filter_var(trim((string) $rawEdicao), FILTER_VALIDATE_INT) !== false
                ? (int) trim((string) $rawEdicao)
                : 0;
            $edicao = $this->edicaoRepo->buscarPorId($idEdicao);
            if ($edicao === null || (int) $edicao['id_livro'] !== (int) $ciclo['id_livro_escolhido']) {
                throw new DomainException('Selecione uma edição física válida pertencente ao livro deste ciclo.');
            }

            $totalPaginas = (int) ($edicao['total_paginas'] ?? 0);
            if ($totalPaginas <= 0) {
                throw new DomainException('A edição escolhida não possui total de páginas cadastrado.');
            }

            $rawPag = $dados['pagina_atual'] ?? null;
            if (!is_scalar($rawPag)) {
                throw new DomainException('Informe o número da página atual.');
            }
            $strPag = trim((string) $rawPag);
            if ($strPag === '' || filter_var($strPag, FILTER_VALIDATE_INT) === false) {
                throw new DomainException('Informe um número inteiro válido para a página atual.');
            }
            $paginaAtual = (int) $strPag;
            if ($paginaAtual < 0 || $paginaAtual > $totalPaginas) {
                throw new DomainException(
                    'A página atual deve ficar entre 0 e ' . $totalPaginas . ' para esta edição.'
                );
            }

            $pct = round(min(100.0, max(0.0, ($paginaAtual / $totalPaginas) * 100.0)), 2);
            if ($paginaAtual >= $totalPaginas && $statusLeitura === 'lendo') {
                $statusLeitura = 'concluido';
            }

            $this->progressoRepo->salvar($idCiclo, $idClube, $idAtor, [
                'formato' => 'fisico',
                'id_edicao' => $idEdicao,
                'pagina_atual' => $paginaAtual,
                'percentual_kindle' => null,
                'status_leitura' => $statusLeitura,
            ]);

            return $pct;
        }

        throw new DomainException('Escolha um formato válido (Kindle ou livro físico).');
    }

    public function salvarAnotacao(int $idCiclo, int $idUsuario, array $dados, ?int $idAnotacao = null): void
    {
        $ciclo = $this->cicloRepo->buscarPorId($idCiclo);
        if ($ciclo === null) {
            throw new DomainException('Ciclo de leitura não encontrado.');
        }

        $idClube = (int) $ciclo['id_clube'];
        $vinculo = Authorization::exigirMembroAtivo($idClube);
        $idAtor = (int) $vinculo['id_usuario'];

        if (($ciclo['clube_status'] ?? '') !== 'ativo') {
            throw new DomainException('Este clube está arquivado e não aceita novas anotações.');
        }

        if ($ciclo['status'] !== 'em_andamento') {
            throw new DomainException('O Caderno de Margens só aceita novas anotações enquanto o ciclo está em andamento.');
        }

        $rawPos = $dados['posicao_pct'] ?? null;
        if (!is_scalar($rawPos)) {
            throw new DomainException('A posição da anotação deve ficar entre 0% e 100%.');
        }
        $strPos = str_replace(',', '.', trim((string) $rawPos));
        if ($strPos === '' || !is_numeric($strPos)) {
            throw new DomainException('Informe um valor numérico válido para a posição da anotação.');
        }
        $posFloat = (float) $strPos;
        if (!is_finite($posFloat) || $posFloat < 0.0 || $posFloat > 100.0) {
            throw new DomainException('A posição da anotação deve ficar entre 0% e 100%.');
        }
        $posicaoPct = round($posFloat, 2);

        // Impede registrar anotação além do próprio progresso para manter a proteção anti-spoiler consistente
        $meuProgresso = $this->progressoRepo->obterPercentualUsuario($idCiclo, $idAtor);
        if ($posicaoPct > $meuProgresso) {
            throw new DomainException(
                'Seu progresso atual está em ' . formatarPercentual($meuProgresso) .
                '. Atualize sua leitura na Sala do Ciclo antes de registrar uma anotação em ' . formatarPercentual($posicaoPct) . '.'
            );
        }

        $idEtapa = (int) ($dados['id_etapa'] ?? 0);
        if ($idEtapa > 0) {
            $etapa = $this->etapaRepo->buscarPorId($idEtapa);
            if ($etapa === null || (int) $etapa['id_ciclo'] !== $idCiclo) {
                throw new DomainException('A etapa selecionada não pertence a este ciclo.');
            }
        } else {
            $idEtapa = null;
        }

        $payload = [
            'id_ciclo' => $idCiclo,
            'id_clube' => $idClube,
            'id_usuario' => $idAtor,
            'id_etapa' => $idEtapa,
            'tipo' => (string) $dados['tipo'],
            'titulo' => $dados['titulo'] ?? null,
            'conteudo' => (string) $dados['conteudo'],
            'posicao_pct' => $posicaoPct,
        ];

        if ($idAnotacao !== null && $idAnotacao > 0) {
            $existente = $this->anotacaoRepo->buscarPorId($idAnotacao);
            if ($existente === null || (int) $existente['id_ciclo'] !== $idCiclo) {
                throw new DomainException('Anotação não encontrada.');
            }
            if ((int) $existente['id_usuario'] !== $idAtor) {
                throw new DomainException('Você só pode editar as suas próprias anotações.');
            }
            $this->anotacaoRepo->atualizar($idAnotacao, $payload);
            return;
        }

        $this->anotacaoRepo->criar($payload);
    }

    // Usado na revelação sob demanda de anotações ocultadas por spoiler.
    public function obterAnotacaoParaRevelacao(int $idAnotacao): ?array
    {
        $usuario = Auth::exigirUsuario(true);
        $anotacao = $this->anotacaoRepo->buscarPorId($idAnotacao);
        if ($anotacao === null) {
            return null;
        }

        $idClube = (int) $anotacao['id_clube'];
        $vinculo = Authorization::obterVinculo($idClube, $usuario['id_usuario']);
        if ($vinculo === null || $vinculo['status'] !== 'ativo') {
            // Em clube privado, retorna null (404 neutro) para não confirmar que a anotação existe
            if (($anotacao['clube_visibilidade'] ?? '') === 'privado') {
                return null;
            }
            Authorization::exigirMembroAtivo($idClube, true);
        }

        return $anotacao;
    }
}
