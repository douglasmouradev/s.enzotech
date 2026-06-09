<?php
/**
 * Armazenamento de imagens de produtos
 */

declare(strict_types=1);

namespace EnzoTech\Services;

class ProdutoStorage
{
    public static function diretorio(): string
    {
        return basePath('uploads/produtos');
    }

    public static function garantirDiretorio(): void
    {
        $dir = self::diretorio();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public static function resolverArquivo(?string $nomeArquivo): ?string
    {
        if ($nomeArquivo === null || $nomeArquivo === '') {
            return null;
        }

        $path = self::diretorio() . '/' . basename($nomeArquivo);
        return is_file($path) ? $path : null;
    }

    public static function excluirArquivo(?string $nomeArquivo): void
    {
        $path = self::resolverArquivo($nomeArquivo);
        if ($path !== null) {
            unlink($path);
        }
    }
}
