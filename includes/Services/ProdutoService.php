<?php
/**
 * Regras de negócio — produtos
 */

declare(strict_types=1);

namespace EnzoTech\Services;

use PDO;
use PDOException;

class ProdutoService
{
    private const MAX_IMAGE_SIZE = 5242880; // 5MB

    /** @var list<string> */
    private const MIME_IMAGEM = ['image/jpeg', 'image/png', 'image/webp'];

    /** @var list<string> */
    private const EXT_IMAGEM = ['jpg', 'jpeg', 'png', 'webp'];

    /** @var array<string, mixed> */
    private array $enums;

    public function __construct(private PDO $pdo)
    {
        $this->enums = require basePath('config/enums.php');
    }

    /**
     * @return array<string, mixed>
     */
    public function parsePost(array $post): array
    {
        return [
            'nome' => trim($post['nome'] ?? ''),
            'marca' => trim($post['marca'] ?? '') ?: null,
            'categoria' => trim($post['categoria'] ?? '') ?: null,
            'sku' => trim($post['sku'] ?? '') ?: null,
            'descricao' => trim($post['descricao'] ?? '') ?: null,
            'preco_compra' => !empty($post['preco_compra']) ? parseMoeda((string) $post['preco_compra']) : null,
            'preco_venda' => !empty($post['preco_venda']) ? parseMoeda((string) $post['preco_venda']) : null,
            'quantidade' => max(0, (int) ($post['quantidade'] ?? 0)),
            'status' => $post['status'] ?? 'ativo',
            'observacoes' => trim($post['observacoes'] ?? '') ?: null,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return string[]
     */
    public function validar(array $data): array
    {
        $erros = [];

        if ($data['nome'] === '') {
            $erros[] = 'Informe o nome do produto.';
        }
        if (mb_strlen($data['nome']) > 150) {
            $erros[] = 'Nome: máximo 150 caracteres.';
        }
        if (!in_array($data['status'], $this->enums['status_produto'], true)) {
            $erros[] = 'Status inválido.';
        }
        if ($data['quantidade'] < 0) {
            $erros[] = 'Quantidade não pode ser negativa.';
        }
        if ($data['preco_compra'] !== null && $data['preco_compra'] < 0) {
            $erros[] = 'Preço de compra inválido.';
        }
        if ($data['preco_venda'] !== null && $data['preco_venda'] < 0) {
            $erros[] = 'Preço de venda inválido.';
        }

        return $erros;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function criar(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO produtos (nome, marca, categoria, sku, descricao, preco_compra, preco_venda,
                quantidade, status, observacoes)
            VALUES (:nome, :marca, :categoria, :sku, :descricao, :preco_compra, :preco_venda,
                :quantidade, :status, :observacoes)
        ");
        $stmt->execute($this->bindParams($data));
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function atualizar(int $id, array $data): void
    {
        $params = $this->bindParams($data);
        $params['id'] = $id;

        $stmt = $this->pdo->prepare("
            UPDATE produtos SET
                nome = :nome, marca = :marca, categoria = :categoria, sku = :sku,
                descricao = :descricao, preco_compra = :preco_compra, preco_venda = :preco_venda,
                quantidade = :quantidade, status = :status, observacoes = :observacoes
            WHERE id = :id
        ");
        $stmt->execute($params);
    }

    /**
     * @param array<string, mixed>|null $file
     * @return string[]
     */
    public function validarImagem(?array $file): array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['Erro ao enviar a imagem do produto.'];
        }

        $nome = (string) ($file['name'] ?? '');
        $tmp = (string) ($file['tmp_name'] ?? '');
        $tamanho = (int) ($file['size'] ?? 0);

        if ($tamanho > self::MAX_IMAGE_SIZE) {
            return ['Imagem muito grande. Máximo 5 MB.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        if (!in_array($mime, self::MIME_IMAGEM, true)) {
            return ['Formato de imagem não permitido. Use JPG, PNG ou WebP.'];
        }

        $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
        if (!in_array($ext, self::EXT_IMAGEM, true)) {
            return ['Extensão de imagem não permitida.'];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $file
     */
    public function salvarImagem(int $produtoId, array $file): string
    {
        ProdutoStorage::garantirDiretorio();

        $nome = (string) $file['name'];
        $tmp = (string) $file['tmp_name'];
        $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));

        $nomeArquivo = 'prod_' . $produtoId . '_' . uniqid('', true) . '.' . $ext;
        $destino = ProdutoStorage::diretorio() . '/' . $nomeArquivo;

        if (!move_uploaded_file($tmp, $destino)) {
            throw new \RuntimeException('Falha ao salvar a imagem do produto.');
        }

        return $nomeArquivo;
    }

    public function definirImagem(int $id, ?string $imagem): void
    {
        $this->pdo->prepare('UPDATE produtos SET imagem = :imagem WHERE id = :id')
            ->execute(['imagem' => $imagem, 'id' => $id]);
    }

    /**
     * @param array<string, mixed>|null $file
     * @return string[]
     */
    public function atualizarImagem(int $id, ?array $file, ?string $imagemAtual, bool $remover): array
    {
        if ($remover) {
            ProdutoStorage::excluirArquivo($imagemAtual);
            $this->definirImagem($id, null);
            return [];
        }

        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        $erros = $this->validarImagem($file);
        if (!empty($erros)) {
            return $erros;
        }

        $novo = $this->salvarImagem($id, $file);
        ProdutoStorage::excluirArquivo($imagemAtual);
        $this->definirImagem($id, $novo);

        return [];
    }

    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT id, imagem FROM produtos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $produto = $stmt->fetch();
        if (!$produto) {
            throw new \RuntimeException('Produto não encontrado.');
        }

        ProdutoStorage::excluirArquivo($produto['imagem'] ?? null);
        $this->pdo->prepare('DELETE FROM produtos WHERE id = :id')->execute(['id' => $id]);
        registrarAuditoria('produto_excluido', 'produto', $id);
    }

    public function mensagemErroDuplicidade(PDOException $e): string
    {
        return $e->getCode() == 23000
            ? 'Este SKU/código já está cadastrado.'
            : erroUsuario($e);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function bindParams(array $data): array
    {
        return [
            'nome' => $data['nome'],
            'marca' => $data['marca'],
            'categoria' => $data['categoria'],
            'sku' => $data['sku'],
            'descricao' => $data['descricao'],
            'preco_compra' => $data['preco_compra'],
            'preco_venda' => $data['preco_venda'],
            'quantidade' => $data['quantidade'],
            'status' => $data['status'],
            'observacoes' => $data['observacoes'],
        ];
    }
}
