<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

final class SecurityTest extends TestCase
{
    public function testValidarCpfValido(): void
    {
        $this->assertTrue(validarCpf('529.982.247-25'));
    }

    public function testValidarCpfInvalido(): void
    {
        $this->assertFalse(validarCpf('111.111.111-11'));
        $this->assertFalse(validarCpf('123'));
    }

    public function testValidarImei15Digitos(): void
    {
        $this->assertTrue(validarImei('490154203237518'));
        $this->assertFalse(validarImei('123'));
    }

    public function testMascararCpf(): void
    {
        $this->assertStringContainsString('***', mascararCpf('529.982.247-25'));
    }

    public function testLimparDigitos(): void
    {
        $this->assertSame('52998224725', limparDigitos('529.982.247-25'));
    }
}
