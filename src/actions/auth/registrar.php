<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\UsuarioRepository;

exigirPost('registro.php');
Csrf::exigirValido('registro.php');
Auth::exigirVisitante('index.php');

$nome = trim((string) ($_POST['nome'] ?? ''));
$apelido = trim((string) ($_POST['apelido'] ?? ''));
$email = mb_strtolower(trim((string) ($_POST['email'] ?? '')), 'UTF-8');
$senha = (string) ($_POST['senha'] ?? '');
$confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

$validator = (new Validator())
    ->obrigatorio('nome', $nome, 'Nome')
    ->tamanho('nome', $nome, 2, 100, 'Nome')
    ->obrigatorio('apelido', $apelido, 'Apelido')
    ->apelido('apelido', $apelido, 'Apelido')
    ->obrigatorio('email', $email, 'E-mail')
    ->email('email', $email, 'E-mail')
    ->obrigatorio('senha', $senha, 'Senha')
    ->senha('senha', $senha, 8, 'Senha')
    ->obrigatorio('confirmar_senha', $confirmarSenha, 'Confirmação de senha')
    ->confirmacao('confirmar_senha', $senha, $confirmarSenha, 'Confirmação de senha');

$usuarioRepo = new UsuarioRepository();

// Só consulta unicidade no banco se o formato dos campos já passou na validação básica
if ($validator->valido()) {
    if ($usuarioRepo->existeApelido($apelido)) {
        $validator->adicionarErro('apelido', 'Este apelido já está em uso. Escolha outro.');
    }
    if ($usuarioRepo->existeEmail($email)) {
        $validator->adicionarErro('email', 'Este e-mail já está cadastrado.');
    }
}

if ($validator->falhou()) {
    Flash::setErrors($validator->erros());
    Flash::setOld($_POST);
    Flash::erro('Revise os campos destacados para concluir seu cadastro.');
    redirect('registro.php');
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);
$idUsuario = $usuarioRepo->criar($nome, $apelido, $email, $senhaHash);

// Já autentica a conta recém-criada para evitar que o leitor precise preencher o login em seguida
Auth::login([
    'id_usuario' => $idUsuario,
    'nome' => $nome,
    'apelido' => $apelido,
    'email' => $email,
]);

Flash::sucesso('Conta criada com sucesso! Seja bem-vindo(a) ao Laço de Papel, @' . $apelido . '.');
redirect('meus-clubes.php');
