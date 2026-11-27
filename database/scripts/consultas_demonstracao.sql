-- Consultas SQL para testar e conferir os dados no phpMyAdmin.

USE laco_de_papel;

-- Clubes públicos ativos com contagem de membros
SELECT
    c.id_clube,
    c.nome,
    c.descricao,
    c.cor_identidade,
    t.nome AS tema,
    COUNT(mc.id_usuario) AS total_membros
FROM clubes c
JOIN temas_clube t ON t.id_tema = c.id_tema
LEFT JOIN membros_clube mc
    ON mc.id_clube = c.id_clube
   AND mc.status = 'ativo'
WHERE c.visibilidade = 'publico'
  AND c.status = 'ativo'
GROUP BY c.id_clube, c.nome, c.descricao, c.cor_identidade, t.nome
ORDER BY c.criado_em DESC
LIMIT 20;

-- Clubes e convites pendentes de um leitor (ex.: rafael)
SELECT
    c.id_clube,
    c.nome,
    c.visibilidade,
    c.status AS status_clube,
    mc.papel,
    mc.status AS status_membro,
    t.nome AS tema,
    conv.apelido AS convidado_por_apelido
FROM membros_clube mc
JOIN clubes c ON c.id_clube = mc.id_clube
JOIN temas_clube t ON t.id_tema = c.id_tema
LEFT JOIN usuarios conv ON conv.id_usuario = mc.convidado_por
WHERE mc.id_usuario = (SELECT id_usuario FROM usuarios WHERE apelido = 'rafael' LIMIT 1)
  AND mc.status IN ('ativo', 'pendente')
ORDER BY mc.status, c.nome;

-- Apuração de votos de um ciclo em votação
SELECT
    i.id_indicacao,
    l.titulo,
    l.autor_principal,
    COUNT(v.id_voto) AS total_votos
FROM indicacoes i
JOIN livros l ON l.id_livro = i.id_livro
LEFT JOIN votos v ON v.id_indicacao = i.id_indicacao
WHERE i.id_ciclo = (SELECT id_ciclo FROM ciclos_leitura WHERE status = 'votacao' LIMIT 1)
GROUP BY i.id_indicacao, l.titulo, l.autor_principal
ORDER BY total_votos DESC, l.titulo ASC;

-- Progresso normalizado individual e média do grupo (calculado inline sem VIEW)
SELECT
    u.apelido,
    p.formato,
    p.status_leitura,
    CASE
        WHEN p.status_leitura = 'concluido' THEN 100.00
        WHEN p.formato = 'kindle' THEN p.percentual_kindle
        WHEN p.formato = 'fisico' AND e.total_paginas > 0
            THEN LEAST(100.00, ROUND((p.pagina_atual / e.total_paginas) * 100, 2))
        ELSE 0.00
    END AS progresso_pct
FROM progresso_leitores p
JOIN usuarios u ON u.id_usuario = p.id_usuario
LEFT JOIN edicoes_livro e ON e.id_edicao = p.id_edicao
WHERE p.id_ciclo = (SELECT id_ciclo FROM ciclos_leitura WHERE status = 'em_andamento' LIMIT 1);

SELECT
    ROUND(AVG(
        CASE
            WHEN p.status_leitura = 'concluido' THEN 100.00
            WHEN p.formato = 'kindle' THEN p.percentual_kindle
            WHEN p.formato = 'fisico' AND e.total_paginas > 0
                THEN LEAST(100.00, ROUND((p.pagina_atual / e.total_paginas) * 100, 2))
            ELSE NULL
        END
    ), 2) AS media_progresso_grupo,
    COUNT(p.id_progresso) AS participantes_com_progresso
FROM progresso_leitores p
LEFT JOIN edicoes_livro e ON e.id_edicao = p.id_edicao
WHERE p.id_ciclo = (SELECT id_ciclo FROM ciclos_leitura WHERE status = 'em_andamento' LIMIT 1);

-- Caderno de Margens com filtro anti-spoiler no SQL (leitor a 25% recebe NULL nas anotações à frente)
SET @id_rafael := (SELECT id_usuario FROM usuarios WHERE apelido = 'rafael' LIMIT 1);
SET @ciclo_ativo := (SELECT id_ciclo FROM ciclos_leitura WHERE status = 'em_andamento' LIMIT 1);
SET @progresso_leitor := 25.00;

SELECT
    a.id_anotacao,
    a.tipo,
    a.posicao_pct,
    u.apelido AS autor,
    CASE
        WHEN a.posicao_pct <= @progresso_leitor OR a.id_usuario = @id_rafael THEN a.titulo
        ELSE NULL
    END AS titulo,
    CASE
        WHEN a.posicao_pct <= @progresso_leitor OR a.id_usuario = @id_rafael THEN a.conteudo
        ELSE NULL
    END AS conteudo,
    (a.posicao_pct > @progresso_leitor AND a.id_usuario <> @id_rafael) AS eh_spoiler,
    a.criado_em
FROM anotacoes a
JOIN usuarios u ON u.id_usuario = a.id_usuario
WHERE a.id_ciclo = @ciclo_ativo
ORDER BY a.criado_em DESC;

-- Checagem de índices e plano de execução
SHOW INDEX FROM clubes;
SHOW INDEX FROM membros_clube;
SHOW INDEX FROM ciclos_leitura;
SHOW INDEX FROM anotacoes;

EXPLAIN
SELECT c.id_clube, c.nome, t.nome AS tema
FROM clubes c
JOIN temas_clube t ON t.id_tema = c.id_tema
WHERE c.visibilidade = 'publico'
  AND c.status = 'ativo'
ORDER BY c.criado_em DESC
LIMIT 20;

-- Opcional (apenas estudo local): VIEW de progresso normalizado.
-- O PHP não depende dela para evitar restrições de CREATE VIEW em hospedagens gratuitas.
/*
CREATE OR REPLACE VIEW vw_progresso_leitores AS
SELECT
    p.id_progresso,
    p.id_ciclo,
    p.id_clube,
    p.id_usuario,
    p.formato,
    p.status_leitura,
    p.atualizado_em,
    CASE
        WHEN p.status_leitura = 'concluido' THEN 100.00
        WHEN p.formato = 'kindle' THEN p.percentual_kindle
        WHEN p.formato = 'fisico' AND e.total_paginas > 0
            THEN LEAST(100.00, ROUND((p.pagina_atual / e.total_paginas) * 100, 2))
        ELSE NULL
    END AS progresso_pct
FROM progresso_leitores p
LEFT JOIN edicoes_livro e ON e.id_edicao = p.id_edicao;
*/
