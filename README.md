# Enzo Tech — Sistema de Controle de Vendas de Celulares

Sistema web profissional para revendedores de celulares.

## Requisitos

- PHP 8.2+ (extensões: `pdo_mysql`, `fileinfo`, `mbstring`)
- MySQL 8.0+
- Apache com `mod_rewrite` (ou Docker)

## Instalação rápida (XAMPP)

1. Copie o projeto para `C:\xampp\htdocs\enzo-tech`
2. Importe o banco:
   ```powershell
   Get-Content database.sql -Raw | mysql -u root -p
   Get-Content database_lgpd.sql -Raw | mysql -u root -p
   Get-Content database_v2.sql -Raw | mysql -u root -p
   Get-Content database_indexes.sql -Raw | mysql -u root -p
   ```
3. Configure `config/database.local.php` e `config/auth.local.php`
4. Opcional: `config/empresa.local.php` (dados LGPD)
5. Acesse `http://localhost/enzo-tech/`

## Docker

```bash
docker compose up -d --build
# Acesse http://localhost:8080
```

## Backup

```powershell
.\scripts\backup.ps1 -DbPass "SUA_SENHA"
```

## Testes

```powershell
# Windows + XAMPP (sem Composer global):
php composer.phar install
C:\xampp\php\php.exe vendor\bin\phpunit
```

## Estrutura do projeto

```
config/           Configuração (DB, auth, empresa, enums)
includes/
  functions.php   Bootstrap da aplicação
  helpers.php     Validações, permissões, enums
  security.php    Sessão, auth, LGPD, auditoria
  partials/       Fragmentos HTML reutilizáveis
  Services/       Regras de negócio (Venda, Celular, Upload, Documentos)
pages/            Controllers por módulo
assets/           CSS e JS globais
tests/            PHPUnit
database/         Documentação de migrações SQL
```

## Funcionalidades

- Dashboard com gráficos, filtros por período e alertas operacionais
- Estoque com aquisição, reserva e fornecedor
- Vendas com wizard, garantia, lock transacional e cancelamento
- Recibo/termo de venda imprimível
- Compradores com listagem, exportação e anonimização LGPD
- Multi-usuário (admin, vendedor, leitura)
- Auditoria, ROPA e política de privacidade
- Menu mobile, ViaCEP, modais de confirmação

## Segurança

- Senha com bcrypt, brute-force protection, sessão segura
- CSRF, PDO prepared statements, upload com MIME real
- Headers HTTP de segurança, logs de auditoria
- Exportações sensíveis via POST

## LGPD

- Consentimento versionado na venda
- Mascaramento de CPF em listagens
- Exportação JSON e anonimização de titulares
- Política, ROPA e trilha de auditoria

## Credenciais padrão

Após migração v2, use o usuário do banco `admin` / `enzo@2025` (altere imediatamente em **Usuários → Alterar senha**).
