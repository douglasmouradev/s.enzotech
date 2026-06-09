<?php
/**
 * Upload unificado de documentos
 */

declare(strict_types=1);

namespace EnzoTech\Services;

use PDO;

class UploadService
{
    private const MAX_SIZE = 10485760; // 10MB
    private const MAX_FILES = 10;

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Processa array $_FILES['documentos'] e grava no banco
     *
     * @return array{enviados: int, erros: string[]}
     */
    public function processar(int $vendaId, array $files, array $descricoes = []): array
    {
        $resultado = ['enviados' => 0, 'erros' => []];

        if (empty($files['name'][0])) {
            return $resultado;
        }

        $uploadDir = DocumentStorage::diretorioVenda($vendaId);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $stmtCount = $this->pdo->prepare('SELECT COUNT(*) FROM documentos WHERE venda_id = :id');
        $stmtCount->execute(['id' => $vendaId]);
        $existentes = (int) $stmtCount->fetchColumn();

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $total = count($files['name']);

        for ($i = 0; $i < $total && ($existentes + $resultado['enviados']) < self::MAX_FILES; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $originalName = $files['name'][$i];
            $tmpName = $files['tmp_name'][$i];
            $tamanho = (int) $files['size'][$i];

            if ($tamanho > self::MAX_SIZE) {
                $resultado['erros'][] = 'Arquivo muito grande: ' . $originalName;
                continue;
            }

            $mimeReal = $finfo->file($tmpName);
            if (!in_array($mimeReal, tiposMimePermitidos(), true)) {
                $resultado['erros'][] = 'Tipo não permitido: ' . $originalName;
                continue;
            }

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, extensoesPermitidas(), true)) {
                $resultado['erros'][] = 'Extensão não permitida: ' . $originalName;
                continue;
            }

            $nomeArquivo = uniqid('doc_', true) . '.' . $ext;
            if (!move_uploaded_file($tmpName, $uploadDir . '/' . $nomeArquivo)) {
                $resultado['erros'][] = 'Falha ao salvar: ' . $originalName;
                continue;
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO documentos (venda_id, nome_original, nome_arquivo, tipo_arquivo, tamanho_bytes, descricao)
                VALUES (:venda_id, :nome_original, :nome_arquivo, :tipo, :tamanho, :descricao)
            ");
            $stmt->execute([
                'venda_id'      => $vendaId,
                'nome_original' => $originalName,
                'nome_arquivo'  => $nomeArquivo,
                'tipo'          => $mimeReal,
                'tamanho'       => $tamanho,
                'descricao'     => trim($descricoes[$i] ?? '') ?: null,
            ]);

            $resultado['enviados']++;
            registrarAuditoria('documento_upload', 'documento', (int) $this->pdo->lastInsertId(), 'Venda #' . $vendaId);
        }

        return $resultado;
    }

    /**
     * Remove documento do disco e banco
     */
    public function excluir(int $documentoId, int $vendaId): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT d.* FROM documentos d
            INNER JOIN vendas v ON v.id = d.venda_id
            WHERE d.id = :id AND d.venda_id = :venda_id
        ');
        $stmt->execute(['id' => $documentoId, 'venda_id' => $vendaId]);
        $doc = $stmt->fetch();

        if (!$doc) {
            return false;
        }

        DocumentStorage::excluirArquivo($vendaId, (string) $doc['nome_arquivo']);

        $this->pdo->prepare('DELETE FROM documentos WHERE id = :id')->execute(['id' => $documentoId]);
        registrarAuditoria('documento_excluido', 'documento', $documentoId, 'Venda #' . $vendaId);

        return true;
    }
}
