<?php
/**
 * Regras de negócio — vendas
 */

declare(strict_types=1);

namespace EnzoTech\Services;

use PDO;
use PDOException;
use Throwable;

class VendaService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Verifica se celular pode ser vendido (lock pessimista)
     */
    public function celularDisponivelParaVenda(int $celularId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.* FROM celulares c
            WHERE c.id = :id AND c.status IN ('disponivel', 'reservado')
            FOR UPDATE
        ");
        $stmt->execute(['id' => $celularId]);
        $celular = $stmt->fetch();

        if (!$celular) {
            return null;
        }

        $stmtV = $this->pdo->prepare("
            SELECT id FROM vendas
            WHERE celular_id = :id AND status_venda = 'ativa'
            LIMIT 1
        ");
        $stmtV->execute(['id' => $celularId]);

        return $stmtV->fetch() ? null : $celular;
    }

    /**
     * Cancela venda ativa e libera celular
     */
    public function cancelar(int $vendaId, string $motivo): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT v.*, c.id AS cid FROM vendas v
            INNER JOIN celulares c ON c.id = v.celular_id
            WHERE v.id = :id AND v.status_venda = 'ativa'
            FOR UPDATE
        ");
        $stmt->execute(['id' => $vendaId]);
        $venda = $stmt->fetch();

        if (!$venda) {
            return false;
        }

        $this->pdo->prepare("
            UPDATE vendas SET status_venda = 'cancelada', cancelada_em = NOW(), motivo_cancelamento = :motivo
            WHERE id = :id
        ")->execute(['motivo' => substr($motivo, 0, 255), 'id' => $vendaId]);

        $this->pdo->prepare("UPDATE celulares SET status = 'disponivel' WHERE id = :id")
            ->execute(['id' => $venda['celular_id']]);

        return true;
    }

    /**
     * Calcula data fim da garantia
     */
    public static function calcularGarantiaAte(string $dataVenda, int $dias): string
    {
        $ts = strtotime($dataVenda . ' +' . $dias . ' days');
        return $ts ? date('Y-m-d', $ts) : $dataVenda;
    }
}
