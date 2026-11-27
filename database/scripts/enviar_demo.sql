-- Dados de demonstração para testes locais (executar após 01_schema.sql e 02_seed_temas.sql).
-- Senha das três contas de teste: Laco@123
--   clara@lacodepapel.test (@clara): responsável dos clubes 1 e 2 (75% em Dom Casmurro)
--   rafael@lacodepapel.test (@rafael): membro do clube 1 (25% físico) e convidado do clube privado 2
--   marina@lacodepapel.test (@marina): responsável do clube 3 (sem acesso ao clube privado 2)

SET NAMES utf8mb4;

USE laco_de_papel;

-- Usuários de teste (hashes gerados com password_hash do PHP)
INSERT IGNORE INTO usuarios (nome, apelido, email, senha_hash, status) VALUES
('Clara Mendes', 'clara', 'clara@lacodepapel.test', '$2y$12$ObzaGvjeSy6jw77U7y1yZ.Z9Xg3/8iZiIRgfjTXl2C6E5BhNc9e/C', 'ativo'),
('Rafael Costa', 'rafael', 'rafael@lacodepapel.test', '$2y$12$Z2mN5uj9Ct8sIl8Ij85chuyTIFh0UgsVpI7suPXaA4iElJHn/YS6S', 'ativo'),
('Marina Alves', 'marina', 'marina@lacodepapel.test', '$2y$12$/FrS6bRyM3S9E62wuR0o7OjIdaeRKtTAj4SUVSzOTRuKnniZCLvZy', 'ativo');

SET @u_clara := (SELECT id_usuario FROM usuarios WHERE apelido = 'clara' LIMIT 1);
SET @u_rafael := (SELECT id_usuario FROM usuarios WHERE apelido = 'rafael' LIMIT 1);
SET @u_marina := (SELECT id_usuario FROM usuarios WHERE apelido = 'marina' LIMIT 1);

SET @t_brasileira := (SELECT id_tema FROM temas_clube WHERE slug = 'literatura-brasileira' LIMIT 1);
SET @t_scifi := (SELECT id_tema FROM temas_clube WHERE slug = 'ficcao-cientifica' LIMIT 1);
SET @t_classicos := (SELECT id_tema FROM temas_clube WHERE slug = 'classicos' LIMIT 1);

-- Clubes de teste (dois públicos e um privado)
INSERT IGNORE INTO clubes (id_tema, id_criador, nome, slug, descricao, regras, visibilidade, cor_identidade, status) VALUES
(
    @t_brasileira,
    @u_clara,
    'Clube das Páginas Brasileiras',
    'clube-das-paginas-brasileiras',
    'Leituras mensais de obras marcantes da literatura brasileira, com conversas calmas e sem pressa.',
    'Respeite o ritmo de cada pessoa e marque corretamente a porcentagem das anotações para evitar spoilers.',
    'publico',
    'vinho',
    'ativo'
),
(
    @t_scifi,
    @u_clara,
    'Mesa Fechada de Ficção',
    'mesa-fechada-de-ficcao',
    'Grupo privado entre amigos para explorar clássicos e histórias de ficção especulativa.',
    'Entrada apenas por convite. Conversas semanais seguindo as etapas combinadas.',
    'privado',
    'verde',
    'ativo'
),
(
    @t_classicos,
    @u_marina,
    'Leituras de Domingo',
    'leituras-de-domingo',
    'Clube aberto para redescobrir clássicos curtos e médios em ciclos tranquilos de um mês.',
    'Todos os membros podem indicar um livro do acervo e votar na próxima leitura.',
    'publico',
    'madeira',
    'ativo'
);

SET @c_brasileiras := (SELECT id_clube FROM clubes WHERE slug = 'clube-das-paginas-brasileiras' LIMIT 1);
SET @c_privado := (SELECT id_clube FROM clubes WHERE slug = 'mesa-fechada-de-ficcao' LIMIT 1);
SET @c_domingo := (SELECT id_clube FROM clubes WHERE slug = 'leituras-de-domingo' LIMIT 1);

-- Membros ativos e convite pendente
INSERT IGNORE INTO membros_clube (id_clube, id_usuario, papel, status, convidado_por, convidado_em, entrou_em) VALUES
(@c_brasileiras, @u_clara, 'responsavel', 'ativo', NULL, NULL, NOW()),
(@c_brasileiras, @u_rafael, 'membro', 'ativo', NULL, NULL, NOW()),
(@c_privado, @u_clara, 'responsavel', 'ativo', NULL, NULL, NOW()),
(@c_privado, @u_rafael, 'membro', 'pendente', @u_clara, NOW(), NULL),
(@c_domingo, @u_marina, 'responsavel', 'ativo', NULL, NULL, NOW());

-- Livros e edições físicas no acervo
INSERT INTO livros (titulo, autor_principal, descricao, ano_primeira_publicacao, idioma_original, fonte_metadados, criado_por)
SELECT 'Dom Casmurro', 'Machado de Assis',
       'Narrado em primeira pessoa por Bento Santiago, o romance revisita as memórias da juventude, o amor por Capitu e as dúvidas que moldaram sua vida.',
       1899, 'pt-BR', 'manual', @u_clara
WHERE NOT EXISTS (SELECT 1 FROM livros WHERE titulo = 'Dom Casmurro' AND autor_principal = 'Machado de Assis');

INSERT INTO livros (titulo, autor_principal, descricao, ano_primeira_publicacao, idioma_original, fonte_metadados, criado_por)
SELECT 'Memórias Póstumas de Brás Cubas', 'Machado de Assis',
       'Um defunto autor narra com ironia e lucidez os episódios de sua existência no Rio de Janeiro do século XIX.',
       1881, 'pt-BR', 'manual', @u_clara
WHERE NOT EXISTS (SELECT 1 FROM livros WHERE titulo = 'Memórias Póstumas de Brás Cubas' AND autor_principal = 'Machado de Assis');

INSERT INTO livros (titulo, autor_principal, descricao, ano_primeira_publicacao, idioma_original, fonte_metadados, criado_por)
SELECT 'Frankenstein', 'Mary Shelley',
       'Ao dar vida a uma criatura por meio da ciência, Victor Frankenstein enfrenta as consequências éticas e humanas de sua ambição.',
       1818, 'en', 'manual', @u_marina
WHERE NOT EXISTS (SELECT 1 FROM livros WHERE titulo = 'Frankenstein' AND autor_principal = 'Mary Shelley');

INSERT INTO livros (titulo, autor_principal, descricao, ano_primeira_publicacao, idioma_original, fonte_metadados, criado_por)
SELECT 'O Alienista', 'Machado de Assis',
       'O médico Simão Bacamarte funda a Casa Verde em Itaguaí para estudar a loucura, provocando questionamentos bem-humorados sobre razão e poder.',
       1882, 'pt-BR', 'manual', @u_marina
WHERE NOT EXISTS (SELECT 1 FROM livros WHERE titulo = 'O Alienista' AND autor_principal = 'Machado de Assis');

SET @l_casmurro := (SELECT id_livro FROM livros WHERE titulo = 'Dom Casmurro' LIMIT 1);
SET @l_memorias := (SELECT id_livro FROM livros WHERE titulo = 'Memórias Póstumas de Brás Cubas' LIMIT 1);
SET @l_frankenstein := (SELECT id_livro FROM livros WHERE titulo = 'Frankenstein' LIMIT 1);
SET @l_alienista := (SELECT id_livro FROM livros WHERE titulo = 'O Alienista' LIMIT 1);

INSERT IGNORE INTO edicoes_livro (id_livro, isbn13, titulo_edicao, editora, ano_publicacao, idioma, total_paginas, fonte_metadados, criado_por) VALUES
(@l_casmurro, '9788594318602', 'Dom Casmurro — Edição de Bolso', 'Principis', 2019, 'pt-BR', 208, 'manual', @u_clara),
(@l_casmurro, '9788582850350', 'Dom Casmurro — Edição Comentada', 'Penguin-Companhia', 2016, 'pt-BR', 256, 'manual', @u_clara),
(@l_memorias, '9788582850015', 'Memórias Póstumas de Brás Cubas', 'Penguin-Companhia', 2014, 'pt-BR', 240, 'manual', @u_clara),
(@l_frankenstein, '9788594318114', 'Frankenstein', 'Principis', 2019, 'pt-BR', 280, 'manual', @u_marina),
(@l_alienista, '9788594318848', 'O Alienista', 'Principis', 2020, 'pt-BR', 112, 'manual', @u_marina);

SET @e_casmurro_208 := (SELECT id_edicao FROM edicoes_livro WHERE isbn13 = '9788594318602' LIMIT 1);

-- Ciclos iniciais: um em andamento no clube 1 e um em votação no clube 3
INSERT INTO ciclos_leitura (
    id_clube, id_livro_escolhido, titulo_ciclo, status,
    data_inicio, data_limite, encontro_em, encontro_local, criado_por
)
SELECT
    @c_brasileiras, @l_casmurro, 'Ciclo 1 — Machado de Assis', 'em_andamento',
    CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    DATE_ADD(NOW(), INTERVAL 14 DAY), 'Encontro online pelo Google Meet', @u_clara
WHERE NOT EXISTS (
    SELECT 1 FROM ciclos_leitura WHERE id_clube = @c_brasileiras AND status = 'em_andamento'
);

INSERT INTO ciclos_leitura (
    id_clube, id_livro_escolhido, titulo_ciclo, status, data_limite, criado_por
)
SELECT
    @c_domingo, NULL, 'Escolha da Leitura do Mês', 'votacao',
    DATE_ADD(CURDATE(), INTERVAL 7 DAY), @u_marina
WHERE NOT EXISTS (
    SELECT 1 FROM ciclos_leitura WHERE id_clube = @c_domingo AND status = 'votacao'
);

SET @ciclo_casmurro := (SELECT id_ciclo FROM ciclos_leitura WHERE id_clube = @c_brasileiras AND status = 'em_andamento' LIMIT 1);
SET @ciclo_votacao := (SELECT id_ciclo FROM ciclos_leitura WHERE id_clube = @c_domingo AND status = 'votacao' LIMIT 1);

-- Indicações e voto inicial no clube 3
INSERT IGNORE INTO indicacoes (id_ciclo, id_clube, id_livro, id_usuario, justificativa) VALUES
(@ciclo_votacao, @c_domingo, @l_frankenstein, @u_marina, 'Um clássico envolvente para discutir ciência, ética e solidão.'),
(@ciclo_votacao, @c_domingo, @l_alienista, @u_marina, 'Leitura curta, irônica e ótima para render conversa em grupo.');

SET @ind_frankenstein := (SELECT id_indicacao FROM indicacoes WHERE id_ciclo = @ciclo_votacao AND id_livro = @l_frankenstein LIMIT 1);

INSERT IGNORE INTO votos (id_ciclo, id_clube, id_indicacao, id_usuario) VALUES
(@ciclo_votacao, @c_domingo, @ind_frankenstein, @u_marina);

-- Etapas de leitura do ciclo em andamento
INSERT IGNORE INTO etapas_leitura (id_ciclo, id_clube, ordem, titulo, pergunta, percentual_alvo, data_limite) VALUES
(@ciclo_casmurro, @c_brasileiras, 1, 'Primeiras memórias e a casa da Matacavalos', 'Como o narrador apresenta a si mesmo nos primeiros capítulos? Você confia no tom dele?', 25.00, DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
(@ciclo_casmurro, @c_brasileiras, 2, 'O seminário e a amizade com Escobar', 'Qual papel Escobar passa a ocupar na vida de Bentinho?', 50.00, DATE_ADD(CURDATE(), INTERVAL 14 DAY)),
(@ciclo_casmurro, @c_brasileiras, 3, 'O casamento e o crescimento da desconfiança', 'Em que momento o olhar de Bentinho muda de fato em relação a Capitu?', 75.00, DATE_ADD(CURDATE(), INTERVAL 21 DAY)),
(@ciclo_casmurro, @c_brasileiras, 4, 'Desfecho e releitura das pistas', 'Ao terminar o livro, o que permanece ambíguo de propósito na narrativa?', 100.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY));

SET @etapa_1 := (SELECT id_etapa FROM etapas_leitura WHERE id_ciclo = @ciclo_casmurro AND ordem = 1 LIMIT 1);
SET @etapa_3 := (SELECT id_etapa FROM etapas_leitura WHERE id_ciclo = @ciclo_casmurro AND ordem = 3 LIMIT 1);

-- Progresso individual (Kindle 75% vs físico pág. 52/208 = 25%)
INSERT IGNORE INTO progresso_leitores (id_ciclo, id_clube, id_usuario, formato, id_edicao, pagina_atual, percentual_kindle, status_leitura) VALUES
(@ciclo_casmurro, @c_brasileiras, @u_clara, 'kindle', NULL, NULL, 75.00, 'lendo'),
(@ciclo_casmurro, @c_brasileiras, @u_rafael, 'fisico', @e_casmurro_208, 52, NULL, 'lendo');

-- Anotações no Caderno de Margens (20% e 25% visíveis para ambos; 60% e 72% ocultadas como spoiler para Rafael)
INSERT INTO anotacoes (id_ciclo, id_clube, id_usuario, id_etapa, tipo, titulo, conteudo, posicao_pct)
SELECT @ciclo_casmurro, @c_brasileiras, @u_clara, @etapa_1, 'reflexao',
       'A voz do narrador desde o início',
       'Logo nos primeiros capítulos dá para notar como Bentinho organiza as lembranças tentando convencer o leitor da sua versão dos fatos.',
       20.00
WHERE NOT EXISTS (SELECT 1 FROM anotacoes WHERE id_ciclo = @ciclo_casmurro AND titulo = 'A voz do narrador desde o início');

INSERT INTO anotacoes (id_ciclo, id_clube, id_usuario, id_etapa, tipo, titulo, conteudo, posicao_pct)
SELECT @ciclo_casmurro, @c_brasileiras, @u_rafael, @etapa_1, 'duvida',
       'José Dias é aliado ou oportunista?',
       'Fiquei em dúvida se o agregado realmente quer ajudar Bentinho ou se apenas tenta manter influência dentro da casa.',
       25.00
WHERE NOT EXISTS (SELECT 1 FROM anotacoes WHERE id_ciclo = @ciclo_casmurro AND titulo = 'José Dias é aliado ou oportunista?');

INSERT INTO anotacoes (id_ciclo, id_clube, id_usuario, id_etapa, tipo, titulo, conteudo, posicao_pct)
SELECT @ciclo_casmurro, @c_brasileiras, @u_clara, @etapa_3, 'teoria',
       'A cena dos olhos de ressaca no velório',
       'A forma como Bentinho descreve o olhar de Capitu na despedida de Escobar parece revelar muito mais o ciúme dele do que uma prova concreta.',
       60.00
WHERE NOT EXISTS (SELECT 1 FROM anotacoes WHERE id_ciclo = @ciclo_casmurro AND titulo = 'A cena dos olhos de ressaca no velório');

INSERT INTO anotacoes (id_ciclo, id_clube, id_usuario, id_etapa, tipo, titulo, conteudo, posicao_pct)
SELECT @ciclo_casmurro, @c_brasileiras, @u_clara, @etapa_3, 'grifo',
       'Obsessão pela semelhança de Ezequiel',
       'Quando a desconfiança se volta para os gestos da criança, fica claro que o narrador já não consegue observar nada sem procurar confirmação.',
       72.00
WHERE NOT EXISTS (SELECT 1 FROM anotacoes WHERE id_ciclo = @ciclo_casmurro AND titulo = 'Obsessão pela semelhança de Ezequiel');
