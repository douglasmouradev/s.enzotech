<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PermissionTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testTemPermissaoHierarquia(): void
    {
        $_SESSION['usuario_logado'] = true;
        $_SESSION['usuario_role'] = 'admin';
        $this->assertTrue(temPermissao('vendedor'));
        $this->assertTrue(temPermissao('admin'));

        $_SESSION['usuario_role'] = 'leitura';
        $this->assertFalse(temPermissao('vendedor'));
        $this->assertTrue(temPermissao('leitura'));
    }

    public function testValidarImeiLuhn(): void
    {
        $this->assertTrue(validarImeiLuhn('490154203237518'));
        $this->assertFalse(validarImeiLuhn('490154203237517'));
    }
}
