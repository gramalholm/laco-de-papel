<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Guarda só o primeiro erro por campo; regras de formato ignoram valor vazio (use obrigatorio() quando aplicável).
final class Validator
{
    private array $errors = [];

    public function obrigatorio(string $field, mixed $value, string $label): self
    {
        if ($this->jaTemErro($field)) {
            return $this;
        }

        if (
            $value === null
            || $value === []
            || (is_string($value) && trim($value) === '')
        ) {
            $this->errors[$field] = sprintf('O campo %s é obrigatório.', $label);
        }

        return $this;
    }

    public function tamanho(string $field, ?string $value, int $min, int $max, string $label): self
    {
        if ($this->jaTemErro($field)) {
            return $this;
        }

        $texto = trim((string) $value);
        if ($texto === '') {
            return $this;
        }

        $len = mb_strlen($texto, 'UTF-8');
        if ($len < $min) {
            $this->errors[$field] = sprintf('%s deve ter pelo menos %d caracteres.', $label, $min);
        } elseif ($len > $max) {
            $this->errors[$field] = sprintf('%s deve ter no máximo %d caracteres.', $label, $max);
        }

        return $this;
    }

    public function email(string $field, ?string $value, string $label = 'E-mail'): self
    {
        if ($this->jaTemErro($field)) {
            return $this;
        }

        $email = trim((string) $value);
        if ($email === '') {
            return $this;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email, 'UTF-8') > 190) {
            $this->errors[$field] = sprintf('Informe um %s válido.', mb_strtolower($label, 'UTF-8'));
        }

        return $this;
    }

    public function apelido(string $field, ?string $value, string $label = 'Apelido'): self
    {
        if ($this->jaTemErro($field)) {
            return $this;
        }

        $apelido = trim((string) $value);
        if ($apelido === '') {
            return $this;
        }

        $len = mb_strlen($apelido, 'UTF-8');
        if ($len < 3 || $len > 40) {
            $this->errors[$field] = sprintf('%s deve ter entre 3 e 40 caracteres.', $label);
        } elseif (preg_match('/^[a-zA-Z0-9._-]+$/', $apelido) !== 1) {
            $this->errors[$field] = sprintf('%s deve conter apenas letras sem acento, números, ponto, hífen ou underline.', $label);
        }

        return $this;
    }

    public function senha(string $field, ?string $value, int $min = 8, string $label = 'Senha'): self
    {
        if ($this->jaTemErro($field)) {
            return $this;
        }

        $senha = (string) $value;
        if ($senha === '') {
            return $this;
        }

        if (mb_strlen($senha, 'UTF-8') < $min) {
            $this->errors[$field] = sprintf('%s deve ter no mínimo %d caracteres.', $label, $min);
        } elseif (mb_strlen($senha, 'UTF-8') > 128) {
            $this->errors[$field] = sprintf('%s excede o limite de caracteres permitido.', $label);
        }

        return $this;
    }

    public function confirmacao(
        string $field,
        ?string $valor,
        ?string $confirmacao,
        string $label = 'Confirmação de senha'
    ): self {
        if ($this->jaTemErro($field)) {
            return $this;
        }

        if ((string) $valor !== (string) $confirmacao) {
            $this->errors[$field] = sprintf('%s não confere.', $label);
        }

        return $this;
    }

    public function inteiro(
        string $field,
        mixed $value,
        ?int $min,
        ?int $max,
        string $label,
        bool $obrigatorio = true
    ): self {
        if ($this->jaTemErro($field)) {
            return $this;
        }

        if ($value !== null && !is_scalar($value)) {
            $this->errors[$field] = sprintf('%s deve ser um número inteiro.', $label);
            return $this;
        }

        $str = trim((string) ($value ?? ''));
        if ($str === '') {
            if ($obrigatorio) {
                $this->errors[$field] = sprintf('O campo %s é obrigatório.', $label);
            }
            return $this;
        }

        if (filter_var($str, FILTER_VALIDATE_INT) === false) {
            $this->errors[$field] = sprintf('%s deve ser um número inteiro.', $label);
            return $this;
        }

        $intVal = (int) $str;
        if ($min !== null && $max !== null && ($intVal < $min || $intVal > $max)) {
            $this->errors[$field] = sprintf('%s deve estar entre %d e %d.', $label, $min, $max);
        } elseif ($min !== null && $intVal < $min) {
            $this->errors[$field] = sprintf('%s deve ser pelo menos %d.', $label, $min);
        } elseif ($max !== null && $intVal > $max) {
            $this->errors[$field] = sprintf('%s deve ser no máximo %d.', $label, $max);
        }

        return $this;
    }

    public function decimal(
        string $field,
        mixed $value,
        ?float $min,
        ?float $max,
        string $label,
        bool $obrigatorio = true
    ): self {
        if ($this->jaTemErro($field)) {
            return $this;
        }

        if ($value !== null && !is_scalar($value)) {
            $this->errors[$field] = sprintf('%s deve ser um número válido.', $label);
            return $this;
        }

        $str = str_replace(',', '.', trim((string) ($value ?? '')));
        if ($str === '') {
            if ($obrigatorio) {
                $this->errors[$field] = sprintf('O campo %s é obrigatório.', $label);
            }
            return $this;
        }

        if (!is_numeric($str)) {
            $this->errors[$field] = sprintf('%s deve ser um número válido.', $label);
            return $this;
        }

        $floatVal = (float) $str;
        if (!is_finite($floatVal)) {
            $this->errors[$field] = sprintf('%s deve ser um número válido.', $label);
            return $this;
        }
        if ($min !== null && $max !== null && ($floatVal < $min || $floatVal > $max)) {
            $this->errors[$field] = sprintf('%s deve estar entre %.0f e %.0f.', $label, $min, $max);
        } elseif ($min !== null && $floatVal < $min) {
            $this->errors[$field] = sprintf('%s deve ser pelo menos %.0f.', $label, $min);
        } elseif ($max !== null && $floatVal > $max) {
            $this->errors[$field] = sprintf('%s deve ser no máximo %.0f.', $label, $max);
        }

        return $this;
    }

    public function urlHttps(
        string $field,
        ?string $value,
        string $label = 'Endereço (URL)',
        bool $obrigatorio = false,
        int $maxLength = 500
    ): self {
        if ($this->jaTemErro($field)) {
            return $this;
        }

        $url = trim((string) $value);
        if ($url === '') {
            if ($obrigatorio) {
                $this->errors[$field] = sprintf('O campo %s é obrigatório.', $label);
            }
            return $this;
        }

        if (mb_strlen($url, 'UTF-8') > $maxLength) {
            $this->errors[$field] = sprintf('%s deve ter no máximo %d caracteres.', $label, $maxLength);
            return $this;
        }

        $valida = filter_var($url, FILTER_VALIDATE_URL) !== false;
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (!$valida || $scheme !== 'https') {
            $this->errors[$field] = sprintf('%s deve ser um endereço válido iniciado por https://.', $label);
        }

        return $this;
    }

    public function dataValida(
        string $field,
        ?string $value,
        string $label = 'Data',
        string $formato = 'Y-m-d'
    ): self {
        if ($this->jaTemErro($field)) {
            return $this;
        }

        $texto = trim((string) $value);
        if ($texto === '') {
            return $this;
        }

        $dt = DateTimeImmutable::createFromFormat('!' . $formato, $texto);
        if ($dt === false || $dt->format($formato) !== $texto) {
            $this->errors[$field] = sprintf('Informe uma %s válida.', mb_strtolower($label, 'UTF-8'));
        }

        return $this;
    }

    public function emLista(string $field, mixed $value, array $permitidos, string $label): self
    {
        if ($this->jaTemErro($field)) {
            return $this;
        }

        if (!in_array($value, $permitidos, true)) {
            $this->errors[$field] = sprintf('Valor inválido selecionado para %s.', $label);
        }

        return $this;
    }

    public function adicionarErro(string $field, string $mensagem): self
    {
        $this->errors[$field] = $mensagem;
        return $this;
    }

    public function falhou(): bool
    {
        return $this->errors !== [];
    }

    public function valido(): bool
    {
        return $this->errors === [];
    }

    public function erros(): array
    {
        return $this->errors;
    }

    public function primeiroErro(): ?string
    {
        if ($this->errors === []) {
            return null;
        }

        return reset($this->errors) ?: null;
    }

    private function jaTemErro(string $field): bool
    {
        return isset($this->errors[$field]);
    }
}
