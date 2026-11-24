# Projeto e Arquitetura de Software (`src/`)

**Tema escolhido:** Clube do Livro *(aguardando o `.md` completo da dupla para detalhar os `models/`, `views/`, `actions/` e `api/` específicos do domínio).*

Este documento registra a **arquitetura de software**, os **atributos de qualidade**, os **padrões de projeto (*Design Patterns*)** e as **Decisões Arquiteturais (ADRs)** adotadas para o sistema da Segunda Avaliação de Desenvolvimento Web.

---

## 1. Direcionadores Arquiteturais (Escopo e Restrições)

Antes de escolher qualquer padrão, a arquitetura foi desenhada a partir das restrições reais do projeto (*Fitness for Purpose*):

- **Escopo funcional:** Sistema web público de pequeno-médio porte (~8–11 telas, ~5–7 tabelas relacionais, CRUD completo, autenticação, interações dinâmicas em JS e consultas analíticas SQL).
- **Equipe:** 2 desenvolvedores (dupla) em paralelo no Git — exige **alta coesão** e **baixo acoplamento** para reduzir conflitos de merge.
- **Stack obrigatória:** HTML5, CSS3, Bootstrap, JavaScript nativo, PHP nativo (POO + PDO) e MySQL/MariaDB — sem dependência de frameworks ou ferramentas de build no servidor.
- **Ambiente de produção (Aula 8):** Hospedagem compartilhada gratuita (**InfinityFree `htdocs/`** ou **x10Hosting `public_html/`**), com raiz web fixa, sistema de arquivos Linux (*case-sensitive*) e sem acesso SSH.
- **Avaliação:** Código auditável, documentado no relatório descritivo e explicável com clareza em até 10 minutos.

---

## 2. Estilo Arquitetural Escolhido

**Monólito Modular em 3 Camadas** (*Apresentação → Aplicação/Controle → Acesso a Dados*), implementado com:
- **Page Controller** nas telas públicas;
- **Zona Privada Isolada (`src/app/`)** com proteção dupla (`.htaccess` + trava de constante PHP);
- **PRG (*Post / Redirect / Get*)** em `src/actions/` para formulários tradicionais;
- **AJAX Progressivo (`fetch` + JSON)** em `src/api/` apenas para micro-interações sem recarregar a página;
- **POO + PDO + Autoloader PSR-4 nativo** (carregado em 1 linha via `src/app/bootstrap.php`, sem Composer).

```text
src/                              ← RAIZ WEB (= htdocs/ no InfinityFree ou public_html/ no x10Hosting)
├── app/                          ← ZONA PRIVADA (1 .htaccess bloqueia acesso direto + trava APP_RUNNING)
│   ├── config/                   ← Database (PDO Singleton/Factory) + config global + *.local.php (.gitignore)
│   ├── core/                     ← Serviços transversais em POO (Auth, Csrf, Flash, Validator, View, Helpers)
│   ├── models/                   ← Camada de Dados — Table Data Gateway / Repository (100% do SQL aqui)
│   ├── views/                    ← Camada de Apresentação reutilizável (Template View / DRY)
│   │   ├── layouts/              ← Estrutura global da página (header, navbar, footer)
│   │   └── components/           ← Componentes visuais reutilizáveis (cards, alerts, modais)
│   └── (futuro: bootstrap.php)   ← Inicializador único: autoloader PSR-4, sessão segura e configuração
├── actions/                      ← Endpoints POST tradicionais (padrão PRG: valida -> persiste -> flash -> redirect)
├── api/                          ← Endpoints JSON leves (mesmo domínio) para interações JS com fetch()
├── assets/                       ← Recursos estáticos (Front-end)
│   ├── css/                      ← Folhas de estilo personalizadas (Bootstrap via CDN)
│   ├── js/                       ← JavaScript modular (validações, DOM, eventos e chamadas fetch)
│   └── img/                      ← Logo, favicon e imagens leves
└── (Na raiz de src/: Page Controllers públicos — index.php, quem-somos.php, cadastro.php, consulta.php, atualizar.php, excluir.php...)
```

---

## 3. Padrões de Projeto (*Design Patterns*) Aplicados

| Padrão | Onde vive | Problema de Engenharia que resolve |
|---|---|---|
| **Page Controller** *(Fowler, PoEAA)* | Raiz de `src/*.php` | Cada URL pública corresponde diretamente a um arquivo fino e legível, eliminando bugs de roteamento `.htaccess` entre WampServer, InfinityFree e x10Hosting. |
| **Table Data Gateway / Repository** | `src/app/models/` | Isola 100% das queries SQL (PDO + Prepared Statements) fora do HTML e dos formulários; facilita testes, manutenção e leitura do DER. |
| **Connection Factory / Singleton** | `src/app/config/` | Garante uma única instância PDO configurada (`utf8mb4`, `ERRMODE_EXCEPTION`, `EMULATE_PREPARES => false`) por requisição HTTP. |
| **Post / Redirect / Get (PRG)** | `src/actions/` | Evita reenvio duplicado de `INSERT`/`UPDATE`/`DELETE` quando o usuário aperta `F5` após submeter um formulário. |
| **Template View & Composição** | `src/app/views/` | Aplica o princípio **DRY** (*Don't Repeat Yourself*), separando estrutura fixa (`layouts/`) de peças repetidas (`components/`). |
| **Progressive Enhancement (AJAX)** | `src/api/` + `assets/js/` | Mantém o sistema sólido em PHP SSR, usando `fetch()` + JSON só onde a UX realmente ganha (voto, filtro dinâmico, atualização de progresso). |
| **Cross-Cutting Concerns Isolados** | `src/app/core/` | Centraliza autenticação, CSRF, mensagens *flash*, validações e escape anti-XSS em um único núcleo reutilizável. |

---

## 4. Fluxos Arquiteturais

### Fluxo 1 — Leitura de Página (`GET`)
```text
Navegador → src/consulta.php (Page Controller)
              ├── require_once __DIR__ . '/app/bootstrap.php'
              ├── Instancia Model em app/models/ → PDO Prepared Statement → MySQL
              └── Renderiza via app/views/layouts/ + app/views/components/ + assets/
```

### Fluxo 2 — Escrita via Formulário (`POST` — Padrão PRG)
```text
Formulário (+ validação JS) → src/actions/salvar.php
                                ├── bootstrap.php → app/core/ (Auth + Csrf + Validator)
                                ├── app/models/ (INSERT / UPDATE / DELETE → MySQL)
                                ├── app/core/Flash (grava feedback na sessão)
                                └── HTTP 302 Redirect → Página GET
```

### Fluxo 3 — Micro-interação Assíncrona (`fetch` — Mesmo Domínio)
```text
Evento JS (ex.: clique em votar) → fetch('api/votar.php', POST + CSRF)
                                     ├── bootstrap.php → Auth + Csrf + Validator
                                     ├── app/models/ (persiste e recalcula total no MySQL)
                                     └── Retorna JSON { ok, total } → JS atualiza o DOM sem reload
```

---

## 5. Registro de Decisões Arquiteturais (ADRs)

- **ADR-01 — Monólito Modular PHP SSR em vez de SPA + API REST:**  
  Alinha-se diretamente ao conteúdo da disciplina (PHP + formulários), evita duplicar lógica de renderização no front-end e elimina conflitos com proteções anti-bot de hospedagens gratuitas em chamadas externas.
- **ADR-02 — Page Controllers em vez de Front Controller com `RewriteRule`:**  
  Reduz risco operacional no deploy (regras de reescrita do Apache variam entre WAMP, InfinityFree e x10Hosting) e deixa o mapa do site explícito para o professor e o relatório.
- **ADR-03 — Zona Privada única (`src/app/`):**  
  Como `htdocs/` e `public_html/` expõem toda a raiz de upload, agrupar `config/`, `core/`, `models/` e `views/` em `src/app/` permite aplicar **Defesa em Profundidade** (1 `.htaccess` + constante `APP_RUNNING`).
- **ADR-04 — Sem camada `services/` obrigatória (*YAGNI*):**  
  Em um escopo de 5–7 tabelas, forçar uma classe `Service` para cada `Model` gera código pass-through redundante. Regras de domínio complexas podem viver em métodos coesos do próprio `Model` ou em um helper pontual em `core/` somente quando realmente houver necessidade.
- **ADR-05 — POO com PDO e Autoloader nativo (sem Composer):**  
  Traz organização orientada a objetos de nível profissional mantendo deploy 100% plug-and-play via FileZilla / File Manager, sem pasta `vendor/` gigante (importante no limite de inodes do x10Hosting).

---

## 6. Matriz de Segurança (OWASP Essencial)

| Risco | Contramedida na Arquitetura |
|---|---|
| **SQL Injection** | 100% das queries em `app/models/` usando **PDO Prepared Statements** (`EMULATE_PREPARES => false`). |
| **XSS (Cross-Site Scripting)** | Toda saída dinâmica no HTML escapada com helper `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`). |
| **CSRF (Cross-Site Request Forgery)** | Token único por sessão validado em `src/actions/` e `src/api/` (`app/core/Csrf.php`). |
| **Exposição de Credenciais** | Credenciais locais em `src/app/config/*.local.php` (bloqueado no `.gitignore`) e pasta `src/app/` fechada ao navegador. |
| **Senhas / Sessão** | `password_hash()` / `password_verify()`, cookies de sessão `HttpOnly` + `SameSite=Lax`, e `session_regenerate_id(true)` no login. |

---

## 7. Divisão de Trabalho Sugerida para a Dupla (Baixo Acoplamento)

A arquitetura permite dois modelos sem conflito no Git:
1. **Divisão por camada:** Pessoa A foca em `app/views/`, `assets/css/`, `assets/js/` e marcação HTML/Bootstrap nas páginas; Pessoa B foca em `database/scripts/`, `app/models/`, `actions/`, `api/` e `app/core/`.
2. **Divisão vertical por módulo/funcionalidade:** Cada pessoa pega uma tela ponta a ponta (seu `Model` + `action` + página + `js`), compartilhando apenas `bootstrap.php` e `app/views/layouts/`.
