# Trabalho Final — Desenvolvimento Web (AV2)

**Instituição:** Universidade Federal Fluminense (UFF) — Campus Rio das Ostras – RJ  
**Curso:** Ciência da Computação  
**Disciplina:** Desenvolvimento Web  
**Professor:** Dr. Eluã Ramos Coutinho  
**Apresentação:** 27/11/2026 (até 10 minutos)

**Tema escolhido:** Clube do Livro  

> Status atual: **Tema definido (Clube do Livro)** — estrutura base e arquitetura configuradas; aguardando o `.md` completo da dupla antes de detalhar escopo e iniciar o código.

---

## 1. Requisitos da Avaliação (Edital AV2)

- **Tecnologias obrigatórias:** HTML5, CSS3, Bootstrap, MySQL, PHP (`mysqli` ou `PDO`) e JavaScript (DOM e eventos).
- **Páginas distintas mínimas:**
  - [ ] Home (`index.php`)
  - [ ] Quem somos (`quem-somos.php`)
  - [ ] Cadastro — *Create*
  - [ ] Consulta — *Read*
  - [ ] Atualização — *Update*
  - [ ] Exclusão — *Delete*
  - [ ] Outras páginas relevantes ao tema (ex.: Login/Perfil, Detalhes, Estatísticas/Relatórios)
- **Banco de Dados (MySQL):**
  - [ ] Múltiplas tabelas relacionadas (chaves primárias, chaves estrangeiras com `ON DELETE CASCADE` / `ON UPDATE CASCADE`, chaves compostas/únicas quando aplicável)
  - [ ] Consultas com `JOIN`, `LIKE`, `ORDER BY`, `GROUP BY` e funções de agregação (`COUNT`, `SUM`, `AVG`, `MIN`, `MAX`)
- **Hospedagem online gratuita:**
  - [ ] Publicado e testado no **InfinityFree** ou **x10Hosting**
- **Entregáveis:**
  - [ ] Sistema web funcional online
  - [ ] Documento descritivo completo (ver `entregaveis/documento-descritivo/ROTEIRO_DOCUMENTO.md`)
  - [ ] Apresentação de até 10 minutos (ver `entregaveis/apresentacao/ROTEIRO_APRESENTACAO.md`)

---

## 2. Estrutura de Pastas do Projeto

Detalhes completos da arquitetura em [docs/ARQUITETURA.md](docs/ARQUITETURA.md).

```text
trabalho-final-devweb/
├── .editorconfig              # Garante UTF-8 (sem BOM) e indentação igual na dupla
├── .gitattributes             # Evita conflitos de quebra de linha (Windows <-> Linux)
├── .gitignore                 # Impede envio de senhas locais, backups e arquivos temporários
├── README.md                  # Este guia
├── docs/                      # PDFs originais das aulas, edital do professor e ARQUITETURA.md
├── database/                  # Banco de dados (NÃO enviar para htdocs/public_html)
│   ├── der/                   # Diagrama Entidade-Relacionamento (arquivo fonte + imagem)
│   ├── scripts/               # Scripts SQL versionados (01_schema.sql, 02_seed.sql, 03_queries.sql)
│   └── backups/               # Exportações manuais do phpMyAdmin (ignoradas pelo Git)
├── entregaveis/               # Materiais de entrega acadêmica (NÃO enviar para o servidor)
│   ├── documento-descritivo/  # Roteiro e versão final do relatório em PDF/DOCX
│   └── apresentacao/          # Roteiro e slides da apresentação de 10 minutos
└── src/                       # RAIZ WEB — enviar SOMENTE o conteúdo desta pasta para a hospedagem
    ├── app/                   # ZONA PRIVADA (bloqueada por 1 .htaccess + trava APP_RUNNING)
    │   ├── config/            # Database (PDO) e configurações (*.local.php ignorado no Git)
    │   ├── core/              # POO transversal (Auth, Csrf, Flash, Validator, View, Helpers)
    │   ├── models/            # Table Data Gateway / Repository — ÚNICO lugar com SQL (PDO)
    │   └── views/
    │       ├── layouts/       # Estrutura global (header, navbar, footer)
    │       └── components/    # Componentes reutilizáveis (cards, alerts, modais)
    ├── actions/               # Endpoints POST de formulários (padrão Post/Redirect/Get)
    ├── api/                   # Endpoints JSON leves (mesmo domínio) para interações JS (fetch)
    └── assets/                # Recursos estáticos (Front-end)
        ├── css/               # Folhas de estilo personalizadas (Bootstrap via CDN)
        ├── js/                # JavaScript (validações, cálculos, DOM, eventos e fetch)
        └── img/               # Logo, favicon e imagens leves
```

> **Por que separar `src/` da raiz do repositório e isolar `src/app/`?**  
> Na hora da hospedagem (Aula 8), basta subir o conteúdo de `src/` para `htdocs/` (InfinityFree) ou `public_html/` (x10Hosting). Assim, `.git/`, `docs/`, `database/` e `entregaveis/` nunca vão ao servidor — e todo código interno de banco, segurança e SQL fica protegido dentro de `src/app/`.

---

## 3. Ambiente de Desenvolvimento Local (Aulas 5 e 6)

### Ferramentas necessárias na máquina da dupla
1. **Pacote AMP:** [WampServer](https://www.wampserver.com/) ou [XAMPP](https://www.apachefriends.org/) (Apache + PHP + MySQL/MariaDB + phpMyAdmin).
2. **Editor:** VS Code (recomendado instalar as extensões *EditorConfig* e *PHP Intelephense*) ou Sublime Text.
3. **Git:** Para versionamento em dupla.
4. **Cliente FTP (para deploy):** [FileZilla Client](https://filezilla-project.org/) (ou usar o *File Manager* online da hospedagem).

### Configuração recomendada no WampServer (Virtual Host)
- Crie um Virtual Host apontando o caminho absoluto diretamente para a pasta `src/` deste repositório (ex.: `C:/.../trabalho-final-devweb/src/`).
- Reinicie os serviços do WampServer (*Restart All Services*).
- **Atenção à porta do MySQL no WampServer:** versões recentes podem usar a porta `3306` (MariaDB) ou `3308` (MySQL). Confira no phpMyAdmin antes de configurar a conexão local.
- Salve as credenciais locais no arquivo ignorado pelo `.gitignore` (`src/app/config/db.local.php`) para nunca vazar senhas no repositório.

---

## 4. Fluxo de Trabalho em Dupla com Git

1. Antes de começar a mexer no código, sempre atualize o repositório:
   ```bash
   git pull origin main
   ```
2. Trabalhem em branches separadas por tarefa (evita conflitos):
   ```bash
   git checkout -b feat/nome-da-tarefa
   ```
3. Faça commits curtos e claros, nunca subindo senhas de banco de dados:
   ```bash
   git status
   git add .
   git commit -m "descreva o que foi feito"
   git push -u origin feat/nome-da-tarefa
   ```
4. Evite que os dois editem o mesmo arquivo ao mesmo tempo (ex.: uma pessoa cuida de uma tela/camada e a outra de outra).

---

## 5. Guia de Hospedagem Gratuita (Baseado na Aula 8)

### Cuidados técnicos importantes antes do deploy
- **Charset/Collation SQL:** Use `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci` nos scripts `.sql`. Evite `utf8mb4_0900_ai_ci` (exclusivo do MySQL 8), pois servidores com MariaDB 10.x (como x10Hosting e alguns servidores do InfinityFree) recusam essa collation na importação.
- **Nomes de arquivos (Case-Sensitive):** Windows não diferencia maiúsculas de minúsculas, mas os servidores Linux da hospedagem **diferenciam**. Use sempre nomes de arquivos e pastas em `letras-minusculas` (ex.: `conexao.php`, `assets/img/logo.png`).
- **Limitações dos planos gratuitos:**
  - InfinityFree **não** suporta MySQL remoto, `mail()` do PHP, Python ou Node.js (apenas PHP + MySQL).
  - Evite depender de uploads pesados de arquivos; mantenha imagens leves e otimizadas.
- **UTF-8 sem BOM:** O `.editorconfig` já garante UTF-8 sem BOM, evitando o erro `Cannot modify header information - headers already sent` ao usar `session_start()` ou `header("Location: ...")`.

### Opção A — InfinityFree (`infinityfree.com`)
1. Crie a conta e um subdomínio gratuito (ex.: `meuprojeto.infinityfreeapp.com`).
2. No **Control Panel (cPanel)** → **MySQL Databases**, crie o banco de dados e anote:
   - `MySQL Host Name` (ex.: `sqlXXX.infinityfree.com`)
   - `MySQL DB Name` (ex.: `if0_XXXXXXX_nome`)
   - `MySQL User Name` (ex.: `if0_XXXXXXX`)
   - `MySQL Password`
3. Ainda no cPanel → **phpMyAdmin** → **Connect Now** → aba **SQL** (ou **Importar**), execute `database/scripts/01_schema.sql` (e `02_seed.sql` se houver dados iniciais).
4. Envie **todo o conteúdo de `src/`** para a pasta **`htdocs/`** usando **FileZilla** (dados em *FTP Details*, porta `21`) ou o **Online File Manager**.
5. Ajuste o arquivo de conexão em `htdocs/app/config/` com as credenciais do InfinityFree.
6. Para depurar durante os testes: cPanel → **Alter PHP Config** → **Alter PHP Directives** → `display_errors = On` *(lembre de voltar para `Off` antes da entrega final!)*.

### Opção B — x10Hosting (`x10hosting.com`)
1. Crie a conta, ative o plano **Free** em *Services* e escolha o subdomínio `.x10.mx` (atenção às regras de nome: sem marcas famosas nem imitação de serviços).
2. No **Hosting Control Panel** → **Account Manager** → **Databases**, crie o banco (anote nome, usuário e senha; o host normalmente é `localhost`).
3. Acesse o **phpMyAdmin** pelo painel e execute o script `.sql` na aba **SQL**.
4. Vá em **System Info & Files** → **File Manager** → pasta **`public_html/`** e envie todo o conteúdo de `src/`.
5. Atualize o arquivo de conexão em `public_html/app/config/` com os dados do banco criado.
