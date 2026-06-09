<?php
/**
 * Regras de negócio — celulares
 */

declare(strict_types=1);

namespace EnzoTech\Services;

use PDO;
use PDOException;

class CelularService
{
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
        $status = $post['status'] ?? 'disponivel';

        return [
            'marca' => trim($post['marca'] ?? ''),
            'modelo' => trim($post['modelo'] ?? ''),
            'serie' => trim($post['serie'] ?? '') ?: null,
            'imei' => limparDigitos($post['imei'] ?? ''),
            'imei2' => ($i2 = limparDigitos($post['imei2'] ?? '')) !== '' ? $i2 : null,
            'cor' => trim($post['cor'] ?? '') ?: null,
            'capacidade' => trim($post['capacidade'] ?? '') ?: null,
            'condicao' => $post['condicao'] ?? 'novo',
            'status' => $status,
            'observacoes' => trim($post['observacoes'] ?? '') ?: null,
            'valor_compra' => !empty($post['valor_compra']) ? parseMoeda((string) $post['valor_compra']) : null,
            'data_compra' => ($post['data_compra'] ?? '') ?: null,
            'fornecedor' => trim($post['fornecedor'] ?? '') ?: null,
            'nota_fiscal_compra' => trim($post['nota_fiscal_compra'] ?? '') ?: null,
            'origem' => $post['origem'] ?? 'fornecedor',
            'reservado_para' => $status === 'reservado' ? (trim($post['reservado_para'] ?? '') ?: null) : null,
            'reservado_ate' => $status === 'reservado' ? (($post['reservado_ate'] ?? '') ?: null) : null,
            'valor_sinal' => $status === 'reservado' && !empty($post['valor_sinal'])
                ? parseMoeda((string) $post['valor_sinal']) : null,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return string[]
     */
    public function validar(array $data, bool $edicao = false, bool $temVendaAtiva = false): array
    {
        $erros = [];

        if ($data['marca'] === '') {
            $erros[] = 'Informe a marca.';
        }
        if ($data['modelo'] === '') {
            $erros[] = 'Informe o modelo.';
        }
        if (!validarImeiLuhn((string) $data['imei'])) {
            $erros[] = 'IMEI inválido (15 dígitos, Luhn).';
        }
        if ($data['imei2'] !== null && !validarImeiLuhn((string) $data['imei2'])) {
            $erros[] = 'IMEI 2 inválido.';
        }
        if (!in_array($data['condicao'], $this->enums['condicoes_celular'], true)) {
            $erros[] = 'Condição inválida.';
        }
        if (!in_array($data['origem'], $this->enums['origens_celular'], true)) {
            $erros[] = 'Origem inválida.';
        }

        $statusPermitidos = $edicao
            ? $this->enums['status_celular']
            : $this->enums['status_celular_cadastro'];

        if (!in_array($data['status'], $statusPermitidos, true)) {
            $erros[] = 'Status inválido.';
        }
        if ($edicao && $data['status'] === 'vendido' && !$temVendaAtiva) {
            $erros[] = 'Status vendido só pode ser definido pelo fluxo de venda.';
        }
        if ($edicao && $temVendaAtiva && $data['status'] !== 'vendido') {
            $erros[] = 'Aparelho possui venda ativa — cancele a venda primeiro.';
        }
        if ($data['status'] === 'reservado' && empty($data['reservado_para'])) {
            $erros[] = 'Informe para quem está reservado.';
        }

        return $erros;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function criar(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO celulares (marca, modelo, serie, imei, imei2, cor, capacidade, condicao, observacoes, status,
                valor_compra, data_compra, fornecedor, nota_fiscal_compra, origem, reservado_para, reservado_ate, valor_sinal)
            VALUES (:marca, :modelo, :serie, :imei, :imei2, :cor, :capacidade, :condicao, :observacoes, :status,
                :valor_compra, :data_compra, :fornecedor, :nota_fiscal, :origem, :reservado_para, :reservado_ate, :valor_sinal)
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
            UPDATE celulares SET
                marca = :marca, modelo = :modelo, serie = :serie,
                imei = :imei, imei2 = :imei2, cor = :cor,
                capacidade = :capacidade, condicao = :condicao,
                observacoes = :observacoes, status = :status,
                valor_compra = :valor_compra, data_compra = :data_compra,
                fornecedor = :fornecedor, nota_fiscal_compra = :nota_fiscal,
                origem = :origem, reservado_para = :reservado_para,
                reservado_ate = :reservado_ate, valor_sinal = :valor_sinal
            WHERE id = :id
        ");
        $stmt->execute($params);
    }

    public function temVendaAtiva(int $celularId): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM vendas WHERE celular_id = :id AND status_venda = 'ativa' LIMIT 1");
        $stmt->execute(['id' => $celularId]);
        return (bool) $stmt->fetch();
    }

    public function mensagemErroDuplicidade(PDOException $e): string
    {
        return $e->getCode() == 23000
            ? 'Este IMEI já está cadastrado.'
            : erroUsuario($e);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function bindParams(array $data): array
    {
        return [
            'marca' => $data['marca'],
            'modelo' => $data['modelo'],
            'serie' => $data['serie'],
            'imei' => $data['imei'],
            'imei2' => $data['imei2'],
            'cor' => $data['cor'],
            'capacidade' => $data['capacidade'],
            'condicao' => $data['condicao'],
            'observacoes' => $data['observacoes'],
            'status' => $data['status'],
            'valor_compra' => $data['valor_compra'],
            'data_compra' => $data['data_compra'] ?: null,
            'fornecedor' => $data['fornecedor'],
            'nota_fiscal' => $data['nota_fiscal_compra'],
            'origem' => $data['origem'],
            'reservado_para' => $data['reservado_para'],
            'reservado_ate' => $data['reservado_ate'],
            'valor_sinal' => $data['valor_sinal'],
        ];
    }
}
