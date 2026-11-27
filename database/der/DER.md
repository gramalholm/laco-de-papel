# Diagrama Entidade-Relacionamento (DER) — Laço de Papel

Modelo relacional do MVP acadêmico com **12 tabelas** em `InnoDB` (`utf8mb4_unicode_ci`).

## 1. Diagrama Mermaid

```mermaid
erDiagram
    USUARIOS ||--o{ CLUBES : "cria (id_criador)"
    TEMAS_CLUBE ||--o{ CLUBES : "categoriza (id_tema)"
    USUARIOS ||--o{ MEMBROS_CLUBE : "participa"
    CLUBES ||--o{ MEMBROS_CLUBE : "possui"
    USUARIOS ||--o{ MEMBROS_CLUBE : "convida (convidado_por)"

    USUARIOS ||--o{ LIVROS : "cadastra (criado_por)"
    LIVROS ||--o{ EDICOES_LIVRO : "possui"
    USUARIOS ||--o{ EDICOES_LIVRO : "cadastra (criado_por)"

    CLUBES ||--o{ CICLOS_LEITURA : "conduz"
    LIVROS ||--o{ CICLOS_LEITURA : "escolhido em (id_livro_escolhido)"
    USUARIOS ||--o{ CICLOS_LEITURA : "abre (criado_por)"

    CICLOS_LEITURA ||--o{ INDICACOES : "recebe"
    LIVROS ||--o{ INDICACOES : "indicado em"
    USUARIOS ||--o{ INDICACOES : "indica"

    CICLOS_LEITURA ||--o{ VOTOS : "registra"
    INDICACOES ||--o{ VOTOS : "recebe"
    USUARIOS ||--o{ VOTOS : "vota"

    CICLOS_LEITURA ||--o{ PROGRESSO_LEITORES : "acompanha"
    USUARIOS ||--o{ PROGRESSO_LEITORES : "atualiza"
    EDICOES_LIVRO ||--o{ PROGRESSO_LEITORES : "referenciada em (opcional)"

    CICLOS_LEITURA ||--o{ ETAPAS_LEITURA : "define"
    CICLOS_LEITURA ||--o{ ANOTACOES : "reune"
    USUARIOS ||--o{ ANOTACOES : "escreve"
    ETAPAS_LEITURA ||--o{ ANOTACOES : "agrupa (opcional)"

    USUARIOS {
        int id_usuario PK
        varchar nome
        varchar apelido UK
        varchar email UK
        varchar senha_hash
        enum status
    }

    TEMAS_CLUBE {
        smallint id_tema PK
        varchar slug UK
        varchar nome UK
        varchar descricao
        tinyint ativo
    }

    CLUBES {
        int id_clube PK
        smallint id_tema FK
        int id_criador FK
        varchar nome
        varchar slug UK
        varchar descricao
        text regras
        enum visibilidade
        enum cor_identidade
        enum status
    }

    MEMBROS_CLUBE {
        int id_membro_clube PK
        int id_clube FK
        int id_usuario FK
        enum papel
        enum status
        int convidado_por FK
        datetime convidado_em
        datetime entrou_em
    }

    LIVROS {
        int id_livro PK
        varchar openlibrary_work_id UK
        varchar titulo
        varchar autor_principal
        text descricao
        smallint ano_primeira_publicacao
        varchar idioma_original
        varchar capa_url
        enum fonte_metadados
        int criado_por FK
    }

    EDICOES_LIVRO {
        int id_edicao PK
        int id_livro FK
        varchar openlibrary_edition_id UK
        varchar isbn10 UK
        varchar isbn13 UK
        varchar editora
        smallint ano_publicacao
        smallint total_paginas
        varchar capa_url
        int criado_por FK
    }

    CICLOS_LEITURA {
        int id_ciclo PK
        int id_clube FK
        int id_livro_escolhido FK
        varchar titulo_ciclo
        enum status
        date data_inicio
        date data_limite
        datetime encontro_em
        varchar encontro_local
        varchar encontro_url
        int criado_por FK
    }

    INDICACOES {
        int id_indicacao PK
        int id_ciclo FK
        int id_clube FK
        int id_livro FK
        int id_usuario FK
        varchar justificativa
    }

    VOTOS {
        int id_voto PK
        int id_ciclo FK
        int id_clube FK
        int id_indicacao FK
        int id_usuario FK
    }

    PROGRESSO_LEITORES {
        int id_progresso PK
        int id_ciclo FK
        int id_clube FK
        int id_usuario FK
        enum formato
        int id_edicao FK
        smallint pagina_atual
        decimal percentual_kindle
        enum status_leitura
    }

    ETAPAS_LEITURA {
        int id_etapa PK
        int id_ciclo FK
        int id_clube FK
        tinyint ordem
        varchar titulo
        varchar pergunta
        decimal percentual_alvo
        date data_limite
    }

    ANOTACOES {
        int id_anotacao PK
        int id_ciclo FK
        int id_clube FK
        int id_usuario FK
        int id_etapa FK
        enum tipo
        varchar titulo
        text conteudo
        decimal posicao_pct
    }
```

## 2. Resumo das 12 Tabelas e Regras de Integridade

| # | Tabela | Chave Primária | Chaves Únicas / Integridade Principal | Papel no Sistema |
|---:|---|---|---|---|
| 1 | `usuarios` | `id_usuario` | `UNIQUE(apelido)`, `UNIQUE(email)` | Contas globais dos leitores; `apelido` permite convites privados. |
| 2 | `temas_clube` | `id_tema` | `UNIQUE(slug)`, `UNIQUE(nome)` | Catálogo controlado de temas para descoberta de clubes. |
| 3 | `clubes` | `id_clube` | `UNIQUE(slug)`, FKs `id_tema`, `id_criador` | Comunidades públicas ou privadas independentes. |
| 4 | `membros_clube` | `id_membro_clube` | `UNIQUE(id_clube, id_usuario)` | Resolve relação N:N, define `papel` por clube e registra convites (`status='pendente'`). |
| 5 | `livros` | `id_livro` | `UNIQUE(openlibrary_work_id)` | Catálogo global compartilhado entre clubes. |
| 6 | `edicoes_livro` | `id_edicao` | `UNIQUE(isbn10)`, `UNIQUE(isbn13)`, `CHECK(total_paginas > 0)` | Edições físicas com paginação para cálculo do progresso físico. |
| 7 | `ciclos_leitura` | `id_ciclo` | `UNIQUE(id_ciclo, id_clube)` | Ciclos por clube; máximo 1 ciclo aberto garantido por transação no `CicloService`. |
| 8 | `indicacoes` | `id_indicacao` | `UNIQUE(id_ciclo, id_livro)`, FK composta `(id_ciclo, id_clube)` | Impede indicar o mesmo livro duas vezes no mesmo ciclo. |
| 9 | `votos` | `id_voto` | `UNIQUE(id_ciclo, id_usuario)`, FK composta `(id_indicacao, id_ciclo, id_clube)` | Garante 1 voto por participante/ciclo na indicação do mesmo clube/ciclo. |
| 10 | `progresso_leitores` | `id_progresso` | `UNIQUE(id_ciclo, id_usuario)`, `CHECK(percentual_kindle 0..100)` | Progresso individual por ciclo (Kindle % ou página física + edição, sem redundância). |
| 11 | `etapas_leitura` | `id_etapa` | `UNIQUE(id_ciclo, ordem)`, `UNIQUE(id_etapa, id_ciclo, id_clube)` | Checkpoints e perguntas de discussão organizados pelo responsável. |
| 12 | `anotacoes` | `id_anotacao` | FK composta opcional `(id_etapa, id_ciclo, id_clube)`, `CHECK(posicao_pct 0..100)` | Caderno de Margens com posição percentual usada na proteção contra spoilers no servidor. |

## 3. Decisões de Normalização

- **Sem papel global em `usuarios`:** uma mesma pessoa pode ser `responsavel` em um clube e `membro` em outro (`membros_clube`).
- **Sem tabela redundante de convites:** convite pendente é um registro em `membros_clube` com `status='pendente'` e `convidado_por`.
- **Obra separada de edição:** `livros` guarda dados da obra; `edicoes_livro` guarda dados que variam por edição física (editora, ISBN, total de páginas).
- **Sem atributo derivado redundante:** o percentual físico não é armazenado em `progresso_leitores`; ele é calculado a partir de `pagina_atual / edicoes_livro.total_paginas`.
- **Integridade multi-clube por FKs compostas:** `indicacoes`, `votos`, `progresso_leitores`, `etapas_leitura` e `anotacoes` carregam `(id_ciclo, id_clube)` referenciando `ciclos_leitura(id_ciclo, id_clube)`, impedindo cruzar dados entre clubes diferentes.
