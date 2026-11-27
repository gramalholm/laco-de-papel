<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\UsuarioRepository;

exigirPost('login.php');
Csrf::exigirValido('login.php');
Auth::exigirVisitante('index.php');

$email = mb_strtolower(trim((string) ($_POST['email'] ?? '')), 'UTF-8');
$senha = (string) ($_POST['senha'] ?? '');

$validator = (new Validator())
    ->obrigatorio('email', $email, 'E-mail')
    ->email('email', $email, 'E-mail')
    ->obrigatorio('senha', $senha, 'Senha');

if ($validator->falhou()) {
    Flash::setErrors($validator->erros());
    Flash::setOld($_POST);
    Flash::erro('Informe seu e-mail e sua senha para entrar.');
    redirect('login.php');
}

$usuarioRepo = new UsuarioRepository();
$usuario = $usuarioRepo->buscarPorEmail($email);

// Usa a mesma mensagem de erro para e-mail inexistente, conta inativa ou senha errada, evitando enumeração de contas
$credenciaisValidas = (
    $usuario !== null
    && ($usuario['status'] ?? '') === 'ativo'
    && password_verify($senha, (string) ($usuario['senha_hash'] ?? ''))
);

if (!$credenciaisValidas) {
    Flash::setOld(['email' => $email]);
    Flash::erro('E-mail ou senha inválidos.');
    redirect('login.php');
}

Auth::login($usuario);
Flash::sucesso('Bom te ver por aqui, @' . $usuario['apelido'] . '!');

// Devolve o leitor para a página protegida que tentou abrir antes do login, se houver
$destino = Auth::consumirDestinoPosLogin('index.php');
redirect($destino);
