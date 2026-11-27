# Laço de Papel

> **Histórias aproximam. Leituras criam laços.**  
> Plataforma colaborativa de clubes de leitura públicos e privados, com escolha coletiva de livros, acompanhamento sincronizado entre Kindle e edição física e Caderno de Margens com proteção real contra spoilers.

**Status do projeto:** MVP completo implementado (12 tabelas relacionais, 15 páginas, 12 repositórios, 3 serviços, 21 ações POST+PRG e 1 endpoint AJAX JSON anti-spoiler).

---

## Funcionalidades Implementadas

- **Autenticação segura:** cadastro de conta com apelido único (`@apelido`), senhas com `password_hash()`/`password_verify()`, regeneração de sessão no login e proteção CSRF em todos os formulários e requisições AJAX.
- **Módulo Multi-Clube (públicos e privados):**
  - Descoberta de clubes públicos por nome e assunto literário (`explorar-clubes.php`);
  - Clubes privados invisíveis em buscas públicas e protegidos com resposta **HTTP 404 neutra** para não membros;
  - Convites por `@apelido`, aceite/recusa de convites, papéis contextuais por clube (`responsavel` e `membro`) e arquivamento preservando histórico.
- **Acervo global de livros e edições físicas:**
  - Cadastro manual de obras e edições físicas com editora, ISBN-10/ISBN-13 e contagem exata de páginas (`acervo.php`, `livro.php`), sem vazar clubes privados na ficha da obra.
- **Ciclos de leitura, votação, progresso e etapas (`ciclo.php`):**
  - Máximo de 1 ciclo aberto por clube (garantido em transação com `SELECT ... FOR UPDATE`);
  - Indicação de obras do acervo, 1 voto trocável por participante e encerramento com tratamento de empate;
  - Progresso unificado de `0%` a `100%` entre **Kindle (%)** e **livro físico (página / total de páginas da edição)**, com prévia em tempo real e acompanhamento coletivo sem rankings competitivos;
  - Etapas percentuais com perguntas de discussão e agenda de encontro do clube.
- **Caderno de Margens e Anti-Spoiler real via AJAX (`caderno.php` + `api/revelar-anotacao.php`):**
  - Anotações (Grifo, Dúvida, Teoria, Reflexão, Comentário) acima da porcentagem atual do leitor têm título e texto mascarados direto no SQL (`NULL`) e **nunca trafegam no HTML inicial**;
  - Revelação explícita via `POST api/revelar-anotacao.php` (autenticação, CSRF, confirmação explícita e checagem de membro ativo no servidor) com inserção segura no DOM via `textContent`.
- **Minha estante, Atualização e Exclusão contextuais (`minha-estante.php`, `atualizar.php`, `excluir.php`).**

---

## Tecnologias

- **Front-end:** HTML5, CSS3 (Design System editorial próprio sobre Bootstrap 5.3), JavaScript nativo (`fetch` + DOM seguro).
- **Back-end:** PHP 8.2+ nativo orientado a objetos com PDO (`EMULATE_PREPARES => false`) e autoloader PSR-4 sem Composer.
- **Banco de Dados:** MySQL 8+/9+ ou MariaDB 10.4+ (`InnoDB`, `utf8mb4_unicode_ci`).

---

## Estrutura do Repositório

```text
laco-de-papel/
├── database/
│   ├── der/DER.md                      # Diagrama Entidade-Relacionamento (Mermaid)
│   └── scripts/                        # 01_schema.sql, 02_seed_temas.sql, 03_seed_demo.sql, 04_consultas_demonstracao.sql
├── docs/
│   ├── ARQUITETURA.md                  # Arquitetura de software e ADRs
│   ├── ASSETS_E_CREDITOS.md            # Fontes, SVGs e imagens editoriais
│   ├── GUIA_DO_CODIGO.md               # Explicação detalhada de cada arquivo e função
│   ├── PLANO_IMPLEMENTACAO.md          # Checklist técnico de implementação
│   └── TESTES.md                       # Roteiro completo de testes com contas de demonstração
└── src/                                # Raiz web (VirtualHost local / htdocs / public_html)
    ├── app/
    │   ├── config/                     # ConnectionFactory + app.example.php (+ app.local.php ignorado no Git)
    │   ├── core/                       # Auth, Authorization, Csrf, Flash, Helpers, Validator, View
    │   ├── models/                     # 12 Repositórios PDO (100% do SQL)
    │   ├── services/                   # ClubeService, CicloService, LeituraService
    │   └── views/                      # layouts/, components/ e pages/
    ├── actions/                        # 21 endpoints POST (padrão PRG)
    ├── api/                            # revelar-anotacao.php (endpoint JSON anti-spoiler)
    ├── assets/                         # css/, js/ e img/
    └── *.php                           # 15 Page Controllers públicos
```

---

## Como Rodar Localmente (WampServer)

1. Importe os scripts no phpMyAdmin nesta ordem:
   - `database/scripts/01_schema.sql`
   - `database/scripts/02_seed_temas.sql`
   - `database/scripts/03_seed_demo.sql` *(opcional: cria 3 contas de teste com senha `Laco@123`, clubes e cenário pronto de anti-spoiler)*
2. Copie `src/app/config/app.example.php` para `src/app/config/app.local.php` e ajuste host/usuário/senha do MySQL local.
3. Configure um VirtualHost no Apache apontando para `src/` (ex.: `http://laco-de-papel.test/`) e abra no navegador.
4. Siga o roteiro em [docs/TESTES.md](docs/TESTES.md) para validar todos os fluxos.

---

## Equipe

- **Gabriel Ramalho Lima** — [@gramalholm](https://github.com/gramalholm)
- **Brenda de Souza Cavalcanti** — [@cavalcantibrenda](https://github.com/cavalcantibrenda)

---

## Disciplina

- **Instituição:** Universidade Federal Fluminense (UFF)
- **Disciplina:** Desenvolvimento Web
- **Professor:** Eluã Ramos Coutinho
