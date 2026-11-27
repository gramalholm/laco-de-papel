-- Carga inicial dos 11 temas de clubes (idempotente).
-- Em hospedagem compartilhada, selecione o banco no phpMyAdmin e remova a linha USE abaixo.

SET NAMES utf8mb4;

USE laco_de_papel;

INSERT INTO temas_clube (slug, nome, descricao, ativo)
VALUES ('literatura-brasileira', 'Literatura Brasileira', 'Autores brasileiros de diferentes épocas, regiões e movimentos literários.', 1)
ON DUPLICATE KEY UPDATE
    nome = 'Literatura Brasileira',
    descricao = 'Autores brasileiros de diferentes épocas, regiões e movimentos literários.',
    ativo = 1;

INSERT INTO temas_clube (slug, nome, descricao, ativo)
VALUES ('classicos', 'Clássicos', 'Obras consagradas da literatura mundial que atravessam gerações.', 1)
ON DUPLICATE KEY UPDATE
    nome = 'Clássicos',
    descricao = 'Obras consagradas da literatura mundial que atravessam gerações.',
    ativo = 1;

INSERT INTO temas_clube (slug, nome, descricao, ativo)
VALUES ('fantasia', 'Fantasia', 'Mundos imaginários, mitologias, magia e grandes jornadas.', 1)
ON DUPLICATE KEY UPDATE
    nome = 'Fantasia',
    descricao = 'Mundos imaginários, mitologias, magia e grandes jornadas.',
    ativo = 1;

INSERT INTO temas_clube (slug, nome, descricao, ativo)
VALUES ('ficcao-cientifica', 'Ficção Científica', 'Futuros possíveis, ciência, tecnologia e especulação sobre a sociedade.', 1)
ON DUPLICATE KEY UPDATE
    nome = 'Ficção Científica',
    descricao = 'Futuros possíveis, ciência, tecnologia e especulação sobre a sociedade.',
    ativo = 1;

INSERT INTO temas_clube (slug, nome, descricao, ativo)
VALUES ('romance', 'Romance', 'Histórias centradas em vínculos, afetos, escolhas e transformações humanas.', 1)
ON DUPLICATE KEY UPDATE
    nome = 'Romance',
    descricao = 'Histórias centradas em vínculos, afetos, escolhas e transformações humanas.',
    ativo = 1;

INSERT INTO temas_clube (slug, nome, descricao, ativo)
VALUES ('suspense-misterio', 'Suspense e Mistério', 'Investigações, enigmas, tensão narrativa e reviravoltas.', 1)
ON DUPLICATE KEY UPDATE
    nome = 'Suspense e Mistério',
    descricao = 'Investigações, enigmas, tensão narrativa e reviravoltas.',
    ativo = 1;

INSERT INTO temas_clube (slug, nome, descricao, ativo)
VALUES ('filosofia', 'Filosofia', 'Ensaios e textos para refletir sobre ética, existência, conhecimento e sentido.', 1)
ON DUPLICATE KEY UPDATE
    nome = 'Filosofia',
    descricao = 'Ensaios e textos para refletir sobre ética, existência, conhecimento e sentido.',
    ativo = 1;

INSERT INTO temas_clube (slug, nome, descricao, ativo)
VALUES ('historia', 'História', 'Leituras históricas, biografias, memórias e contextos que moldaram o mundo.', 1)
ON DUPLICATE KEY UPDATE
    nome = 'História',
    descricao = 'Leituras históricas, biografias, memórias e contextos que moldaram o mundo.',
    ativo = 1;

INSERT INTO temas_clube (slug, nome, descricao, ativo)
VALUES ('poesia', 'Poesia', 'Versos, antologias e leituras atentas ao ritmo, à imagem e à linguagem.', 1)
ON DUPLICATE KEY UPDATE
    nome = 'Poesia',
    descricao = 'Versos, antologias e leituras atentas ao ritmo, à imagem e à linguagem.',
    ativo = 1;

INSERT INTO temas_clube (slug, nome, descricao, ativo)
VALUES ('nao-ficcao', 'Não Ficção', 'Reportagens, ciência, cultura, ensaios e livros sobre o mundo real.', 1)
ON DUPLICATE KEY UPDATE
    nome = 'Não Ficção',
    descricao = 'Reportagens, ciência, cultura, ensaios e livros sobre o mundo real.',
    ativo = 1;

INSERT INTO temas_clube (slug, nome, descricao, ativo)
VALUES ('variados', 'Variados', 'Clubes abertos a diferentes gêneros, alternando estilos a cada ciclo.', 1)
ON DUPLICATE KEY UPDATE
    nome = 'Variados',
    descricao = 'Clubes abertos a diferentes gêneros, alternando estilos a cada ciclo.',
    ativo = 1;
