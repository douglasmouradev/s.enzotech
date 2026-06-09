<?php
/**
 * Regras de negócio — compradores
 */

declare(strict_types=1);

namespace EnzoTech\Services;

use PDO;

class CompradorService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function temVendas(int $compradorId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM vendas WHERE comprador_id = :id LIMIT 1');
        $stmt->execute(['id' => $compradorId]);
        return (bool) $stmt->fetch();
    }

    /**
     * @return array{ok: bool, motivo: string}
     */
    public function podeExcluir(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome_completo FROM compradores WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            return ['ok' => false, 'motivo' => 'Comprador não encontrado.'];
        }
        if ($this->temVendas($id)) {
            return ['ok' => false, 'motivo' => 'Não é possível excluir comprador com vendas registradas. Use anonimização LGPD.'];
        }
        return ['ok' => true, 'motivo' => ''];
    }

    public function excluir(int $id): void
    {
        $check = $this->podeExcluir($id);
        if (!$check['ok']) {
            throw new \RuntimeException($check['motivo']);
        }

        $stmt = $this->pdo->prepare('DELETE FROM compradores WHERE id = :id');
        $stmt->execute(['id' => $id]);
        registrarAuditoria('comprador_excluido', 'comprador', $id);
    }
}
