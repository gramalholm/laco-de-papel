<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\View;
use App\Models\CicloRepository;
use App\Models\EdicaoRepository;
use App\Models\LivroRepository;

$idLivro = (int) ($_GET['id'] ?? 0);
if ($idLivro <= 0) {
    View::renderError(404, 'Livro não encontrado', 'A obra solicitada não foi localizada no acervo.');
}

$livroRepo = new LivroRepository();
$livro = $livroRepo->buscarPorId($idLivro);
if ($livro === null) {
    View::renderError(404, 'Livro não encontrado', 'A obra solicitada não foi localizada no acervo.');
}

$usuario = Auth::user();
$edicaoRepo = new EdicaoRepository();
$cicloRepo = new CicloRepository();

$edicoes = $edicaoRepo->listarPorLivro($idLivro);
$ciclosVisiveis = $cicloRepo->listarCiclosVisiveisDoLivro($idLivro, $usuario !== null ? $usuario['id_usuario'] : null);

// Usa a capa da obra como padrão e cai para a primeira edição com capa se ela estiver vazia
$capaExibicao = (string) ($livro['capa_url'] ?? '');
if ($capaExibicao === '') {
    foreach ($edicoes as $ed) {
        if (!empty($ed['capa_url'])) {
            $capaExibicao = (string) $ed['capa_url'];
            break;
        }
    }
}

// Apenas quem cadastrou a obra no acervo vê os atalhos de edição e exclusão
$podeGerenciarLivro = (
    $usuario !== null
    && $livro['criado_por'] !== null
    && (int) $livro['criado_por'] === $usuario['id_usuario']
);

View::render('livro', [
    'pageTitle' => $livro['titulo'] . ' — Laço de Papel',
    'activeNav' => 'acervo',
    'useBands' => true,
    'scripts' => ['assets/js/catalog.js'],
    'usuario' => $usuario,
    'livro' => $livro,
    'capaExibicao' => $capaExibicao,
    'edicoes' => $edicoes,
    'ciclosVisiveis' => $ciclosVisiveis,
    'podeGerenciarLivro' => $podeGerenciarLivro,
]);
