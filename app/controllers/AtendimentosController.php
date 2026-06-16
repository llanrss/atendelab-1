<?php

class AtendimentosController
{
    private PDO $pdo;

    public function __construct()
    {
        require __DIR__ . '/../../config/database.php';
        $this->pdo = $pdo;
    }

    public function listar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $sql = "SELECT
                    a.id,
                    p.nome AS pessoa,
                    t.descricao AS tipo_atendimento,
                    u.nome AS atendente,
                    a.status,
                    a.data_atendimento,
                    a.criado_em
                FROM atendimentos a
                INNER JOIN pessoas p
                    ON a.pessoa_id = p.id
                INNER JOIN tipos_atendimentos t
                    ON a.tipo_atendimento_id = t.id
                INNER JOIN usuarios u
                    ON a.usuario_id = u.id
                ORDER BY a.id DESC";

        $stmt = $this->pdo->query($sql);

        echo json_encode(
            $stmt->fetchAll(PDO::FETCH_ASSOC),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
    }

    public function visualizar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID inválido.']);
            return;
        }

        $sql = "SELECT
                    a.*,
                    p.nome AS pessoa,
                    p.cpf,
                    p.telefone,
                    p.email,
                    t.descricao AS tipo_atendimento,
                    u.nome AS atendente
                FROM atendimentos a
                INNER JOIN pessoas p
                    ON a.pessoa_id = p.id
                INNER JOIN tipos_atendimentos t
                    ON a.tipo_atendimento_id = t.id
                INNER JOIN usuarios u
                    ON a.usuario_id = u.id
                WHERE a.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $atendimento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$atendimento) {
            http_response_code(404);
            echo json_encode([
                'erro' => 'Atendimento não encontrado.'
            ]);
            return;
        }

        echo json_encode(
            $atendimento,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
    }

    public function criar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $pessoaId = filter_input(
            INPUT_POST,
            'pessoa_id',
            FILTER_VALIDATE_INT
        );

        $tipoAtendimentoId = filter_input(
            INPUT_POST,
            'tipo_atendimento_id',
            FILTER_VALIDATE_INT
        );

        $usuarioId = filter_input(
            INPUT_POST,
            'usuario_id',
            FILTER_VALIDATE_INT
        );

        $descricao = trim($_POST['descricao'] ?? '');

        if (
            !$pessoaId ||
            !$tipoAtendimentoId ||
            !$usuarioId
        ) {
            http_response_code(400);

            echo json_encode([
                'erro' => 'Pessoa, tipo de atendimento e usuário são obrigatórios.'
            ]);

            return;
        }

        try {
            $sql = "INSERT INTO atendimentos
                    (
                        pessoa_id,
                        tipo_atendimento_id,
                        usuario_id,
                        descricao
                    )
                    VALUES
                    (
                        :pessoa_id,
                        :tipo_atendimento_id,
                        :usuario_id,
                        :descricao
                    )";

            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(':pessoa_id', $pessoaId, PDO::PARAM_INT);
            $stmt->bindValue(':tipo_atendimento_id', $tipoAtendimentoId, PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':descricao', $descricao);

            $stmt->execute();

            http_response_code(201);

            echo json_encode([
                'mensagem' => 'Atendimento cadastrado com sucesso.',
                'id' => $this->pdo->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {
            http_response_code(500);

            echo json_encode([
                'erro' => 'Erro ao cadastrar atendimento.'
            ]);
        }
    }

    public function atualizarStatus(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? '';

        if (!$id) {
            http_response_code(400);

            echo json_encode([
                'erro' => 'ID inválido.'
            ]);

            return;
        }

        if (
            !in_array(
                $status,
                [
                    'aberto',
                    'em_andamento',
                    'finalizado',
                    'cancelado'
                ],
                true
            )
        ) {
            http_response_code(400);

            echo json_encode([
                'erro' => 'Status inválido.'
            ]);

            return;
        }

        try {
            $sql = "UPDATE atendimentos
                    SET status = :status
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $stmt->execute();

            echo json_encode([
                'mensagem' => 'Status atualizado com sucesso.'
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {
            http_response_code(500);

            echo json_encode([
                'erro' => 'Erro ao atualizar status.'
            ]);
        }
    }
}
