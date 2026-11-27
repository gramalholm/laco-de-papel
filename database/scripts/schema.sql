-- Estrutura do banco de dados do Laço de Papel (MySQL 8+/9+ e MariaDB 10.4+).
-- Em hospedagem compartilhada, o banco já vem criado pelo painel: remova ou comente
-- o CREATE DATABASE e o USE abaixo e selecione o banco no phpMyAdmin antes de importar.

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS laco_de_papel
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE laco_de_papel;

-- Contas de leitores (sem papel administrativo global)
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    apelido VARCHAR(40) NOT NULL,
    email VARCHAR(190) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_apelido (apelido),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assuntos/gêneros disponíveis na criação e filtragem de clubes
CREATE TABLE IF NOT EXISTS temas_clube (
    id_tema SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL,
    nome VARCHAR(80) NOT NULL,
    descricao VARCHAR(255) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_temas_slug (slug),
    UNIQUE KEY uq_temas_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clubes de leitura (públicos ou privados)
CREATE TABLE IF NOT EXISTS clubes (
    id_clube INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_tema SMALLINT UNSIGNED NOT NULL,
    id_criador INT UNSIGNED NOT NULL,
    nome VARCHAR(120) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    descricao VARCHAR(800) NOT NULL,
    regras TEXT NULL,
    visibilidade ENUM('publico', 'privado') NOT NULL DEFAULT 'publico',
    cor_identidade ENUM('vinho', 'verde', 'madeira') NOT NULL DEFAULT 'vinho',
    status ENUM('ativo', 'arquivado') NOT NULL DEFAULT 'ativo',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clubes_slug (slug),
    KEY idx_clubes_descoberta (visibilidade, status, id_tema, criado_em),
    KEY idx_clubes_nome (nome),
    CONSTRAINT fk_clubes_tema FOREIGN KEY (id_tema) REFERENCES temas_clube (id_tema),
    CONSTRAINT fk_clubes_criador FOREIGN KEY (id_criador) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vínculos entre leitores e clubes (papel contextual e controle de convites)
CREATE TABLE IF NOT EXISTS membros_clube (
    id_membro_clube INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_clube INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    papel ENUM('responsavel', 'membro') NOT NULL DEFAULT 'membro',
    status ENUM('pendente', 'ativo', 'recusado', 'saiu') NOT NULL DEFAULT 'pendente',
    convidado_por INT UNSIGNED NULL,
    convidado_em DATETIME NULL,
    entrou_em DATETIME NULL,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_membro_clube_usuario (id_clube, id_usuario),
    KEY idx_membros_usuario_status (id_usuario, status),
    KEY idx_membros_clube_status_papel (id_clube, status, papel),
    CONSTRAINT fk_membros_clube FOREIGN KEY (id_clube) REFERENCES clubes (id_clube),
    CONSTRAINT fk_membros_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario),
    CONSTRAINT fk_membros_convidado_por FOREIGN KEY (convidado_por) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Acervo compartilhado de obras
CREATE TABLE IF NOT EXISTS livros (
    id_livro INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    openlibrary_work_id VARCHAR(40) NULL,
    titulo VARCHAR(250) NOT NULL,
    titulo_original VARCHAR(250) NULL,
    autor_principal VARCHAR(180) NOT NULL,
    descricao TEXT NULL,
    ano_primeira_publicacao SMALLINT UNSIGNED NULL,
    idioma_original VARCHAR(20) NULL,
    capa_url VARCHAR(700) NULL,
    fonte_metadados ENUM('openlibrary', 'manual') NOT NULL DEFAULT 'manual',
    url_fonte VARCHAR(500) NULL,
    criado_por INT UNSIGNED NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_livros_openlibrary_work (openlibrary_work_id),
    KEY idx_livros_titulo (titulo),
    KEY idx_livros_autor (autor_principal),
    CONSTRAINT fk_livros_criador FOREIGN KEY (criado_por) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Edições físicas de uma obra (usadas na conversão de página atual para percentual)
CREATE TABLE IF NOT EXISTS edicoes_livro (
    id_edicao INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_livro INT UNSIGNED NOT NULL,
    openlibrary_edition_id VARCHAR(40) NULL,
    isbn10 VARCHAR(10) NULL,
    isbn13 VARCHAR(13) NULL,
    titulo_edicao VARCHAR(250) NULL,
    editora VARCHAR(180) NULL,
    ano_publicacao SMALLINT UNSIGNED NULL,
    idioma VARCHAR(20) NULL,
    total_paginas SMALLINT UNSIGNED NULL,
    capa_url VARCHAR(700) NULL,
    url_fonte VARCHAR(500) NULL,
    credito_capa VARCHAR(255) NULL,
    fonte_metadados ENUM('openlibrary', 'manual') NOT NULL DEFAULT 'manual',
    criado_por INT UNSIGNED NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_edicoes_openlibrary (openlibrary_edition_id),
    UNIQUE KEY uq_edicoes_isbn10 (isbn10),
    UNIQUE KEY uq_edicoes_isbn13 (isbn13),
    KEY idx_edicoes_livro_idioma (id_livro, idioma),
    CONSTRAINT fk_edicoes_livro FOREIGN KEY (id_livro) REFERENCES livros (id_livro),
    CONSTRAINT fk_edicoes_criador FOREIGN KEY (criado_por) REFERENCES usuarios (id_usuario),
    CONSTRAINT chk_edicoes_paginas CHECK (total_paginas IS NULL OR total_paginas > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ciclos de leitura por clube (no máximo um ciclo aberto por vez)
CREATE TABLE IF NOT EXISTS ciclos_leitura (
    id_ciclo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_clube INT UNSIGNED NOT NULL,
    id_livro_escolhido INT UNSIGNED NULL,
    titulo_ciclo VARCHAR(120) NULL,
    status ENUM('votacao', 'em_andamento', 'encerrado', 'cancelado') NOT NULL DEFAULT 'votacao',
    data_inicio DATE NULL,
    data_limite DATE NULL,
    data_encerramento DATETIME NULL,
    encontro_em DATETIME NULL,
    encontro_local VARCHAR(255) NULL,
    encontro_url VARCHAR(500) NULL,
    criado_por INT UNSIGNED NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ciclo_id_clube (id_ciclo, id_clube),
    KEY idx_ciclos_clube_status (id_clube, status, criado_em),
    CONSTRAINT fk_ciclos_clube FOREIGN KEY (id_clube) REFERENCES clubes (id_clube),
    CONSTRAINT fk_ciclos_livro FOREIGN KEY (id_livro_escolhido) REFERENCES livros (id_livro),
    CONSTRAINT fk_ciclos_criador FOREIGN KEY (criado_por) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indicações de livros na votação do ciclo
CREATE TABLE IF NOT EXISTS indicacoes (
    id_indicacao INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_ciclo INT UNSIGNED NOT NULL,
    id_clube INT UNSIGNED NOT NULL,
    id_livro INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    justificativa VARCHAR(500) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_indicacao_ciclo_livro (id_ciclo, id_livro),
    UNIQUE KEY uq_indicacao_ciclo_clube (id_indicacao, id_ciclo, id_clube),
    KEY idx_indicacoes_ciclo_data (id_ciclo, criado_em),
    CONSTRAINT fk_indicacoes_ciclo FOREIGN KEY (id_ciclo, id_clube)
        REFERENCES ciclos_leitura (id_ciclo, id_clube),
    CONSTRAINT fk_indicacoes_livro FOREIGN KEY (id_livro) REFERENCES livros (id_livro),
    CONSTRAINT fk_indicacoes_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Votos da rodada (um por participante em cada ciclo)
CREATE TABLE IF NOT EXISTS votos (
    id_voto INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_ciclo INT UNSIGNED NOT NULL,
    id_clube INT UNSIGNED NOT NULL,
    id_indicacao INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_voto_usuario_ciclo (id_ciclo, id_usuario),
    KEY idx_votos_indicacao (id_indicacao),
    CONSTRAINT fk_votos_ciclo FOREIGN KEY (id_ciclo, id_clube)
        REFERENCES ciclos_leitura (id_ciclo, id_clube),
    CONSTRAINT fk_votos_indicacao_ciclo FOREIGN KEY (id_indicacao, id_ciclo, id_clube)
        REFERENCES indicacoes (id_indicacao, id_ciclo, id_clube),
    CONSTRAINT fk_votos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Progresso individual de leitura por ciclo (Kindle % ou edição física)
CREATE TABLE IF NOT EXISTS progresso_leitores (
    id_progresso INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_ciclo INT UNSIGNED NOT NULL,
    id_clube INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    formato ENUM('kindle', 'fisico') NOT NULL,
    id_edicao INT UNSIGNED NULL,
    pagina_atual SMALLINT UNSIGNED NULL,
    percentual_kindle DECIMAL(5, 2) NULL,
    status_leitura ENUM('lendo', 'pausado', 'concluido') NOT NULL DEFAULT 'lendo',
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_progresso_ciclo_usuario (id_ciclo, id_usuario),
    KEY idx_progresso_ciclo_status (id_ciclo, status_leitura),
    KEY idx_progresso_usuario_data (id_usuario, atualizado_em),
    KEY idx_progresso_edicao (id_edicao),
    CONSTRAINT fk_progresso_ciclo FOREIGN KEY (id_ciclo, id_clube)
        REFERENCES ciclos_leitura (id_ciclo, id_clube),
    CONSTRAINT fk_progresso_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario),
    CONSTRAINT fk_progresso_edicao FOREIGN KEY (id_edicao) REFERENCES edicoes_livro (id_edicao),
    CONSTRAINT chk_progresso_kindle CHECK (
        percentual_kindle IS NULL OR (percentual_kindle >= 0 AND percentual_kindle <= 100)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Etapas e perguntas de discussão do ciclo
CREATE TABLE IF NOT EXISTS etapas_leitura (
    id_etapa INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_ciclo INT UNSIGNED NOT NULL,
    id_clube INT UNSIGNED NOT NULL,
    ordem TINYINT UNSIGNED NOT NULL,
    titulo VARCHAR(140) NOT NULL,
    pergunta VARCHAR(700) NULL,
    percentual_alvo DECIMAL(5, 2) NOT NULL,
    data_limite DATE NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_etapa_ciclo_ordem (id_ciclo, ordem),
    UNIQUE KEY uq_etapa_ciclo_clube (id_etapa, id_ciclo, id_clube),
    KEY idx_etapas_ciclo_percentual (id_ciclo, percentual_alvo),
    CONSTRAINT fk_etapas_ciclo FOREIGN KEY (id_ciclo, id_clube)
        REFERENCES ciclos_leitura (id_ciclo, id_clube),
    CONSTRAINT chk_etapa_percentual CHECK (percentual_alvo >= 0 AND percentual_alvo <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Anotações do Caderno de Margens com posição percentual para proteção contra spoilers
CREATE TABLE IF NOT EXISTS anotacoes (
    id_anotacao INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_ciclo INT UNSIGNED NOT NULL,
    id_clube INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    id_etapa INT UNSIGNED NULL,
    tipo ENUM('grifo', 'duvida', 'teoria', 'reflexao', 'comentario') NOT NULL,
    titulo VARCHAR(160) NULL,
    conteudo TEXT NOT NULL,
    posicao_pct DECIMAL(5, 2) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_anotacoes_ciclo_data (id_ciclo, criado_em),
    KEY idx_anotacoes_ciclo_tipo_data (id_ciclo, tipo, criado_em),
    KEY idx_anotacoes_ciclo_posicao (id_ciclo, posicao_pct),
    KEY idx_anotacoes_usuario_data (id_usuario, criado_em),
    CONSTRAINT fk_anotacoes_ciclo FOREIGN KEY (id_ciclo, id_clube)
        REFERENCES ciclos_leitura (id_ciclo, id_clube),
    CONSTRAINT fk_anotacoes_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario),
    CONSTRAINT fk_anotacoes_etapa FOREIGN KEY (id_etapa, id_ciclo, id_clube)
        REFERENCES etapas_leitura (id_etapa, id_ciclo, id_clube),
    CONSTRAINT chk_anotacoes_posicao CHECK (posicao_pct >= 0 AND posicao_pct <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
