<?php
/**
 * Caminhos seguros para arquivos de documentos
 */

declare(strict_types=1);

namespace EnzoTech\Services;

class DocumentStorage
{
    public static function diretorioVenda(int $vendaId): string
    {
        return basePath('uploads/documentos/' . $vendaId);
    }

    /**
     * Resolve caminho real do arquivo com proteção contra path traversal
     */
    public static function resolverArquivo(int $vendaId, string $nomeArquivo): ?string
    {
        $dirBase = realpath(self::diretorioVenda($vendaId));
        if ($dirBase === false) {
            return null;
        }

        $nomeSeguro = basename($nomeArquivo);
        $caminho = $dirBase . DIRECTORY_SEPARATOR . $nomeSeguro;
        $arquivoReal = is_file($caminho) ? realpath($caminho) : false;

        if ($arquivoReal === false || !str_starts_with($arquivoReal, $dirBase)) {
            return null;
        }

        return $arquivoReal;
    }

    public static function excluirArquivo(int $vendaId, string $nomeArquivo): bool
    {
        $arquivo = self::resolverArquivo($vendaId, $nomeArquivo);
        if ($arquivo === null) {
            return false;
        }

        return @unlink($arquivo);
    }
}
