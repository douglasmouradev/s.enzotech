# Deploy — enzotech.tdesksolutions.com.br

## Dados do servidor

| Item | Valor |
|------|--------|
| Domínio | `enzotech.tdesksolutions.com.br` |
| Raiz do site | `/www/wwwroot/enzotech.tdesksolutions.com.br` |
| Repositório | https://github.com/douglasmouradev/s.enzotech |

---

## Clone na VPS (comando principal)

```bash
mkdir -p /www/wwwroot/enzotech.tdesksolutions.com.br
cd /www/wwwroot/enzotech.tdesksolutions.com.br
git clone https://github.com/douglasmouradev/s.enzotech.git .
```

Ou com o script automático:

```bash
cd /www/wwwroot/enzotech.tdesksolutions.com.br
git clone https://github.com/douglasmouradev/s.enzotech.git .
sudo bash deploy/install-vps.sh
```

---

## aaPanel / BT Panel

### 1. Criar o site

1. **Website** → **Add site**
2. Domínio: `enzotech.tdesksolutions.com.br`
3. **Root directory:** `/www/wwwroot/enzotech.tdesksolutions.com.br`
4. PHP: **8.2+**
5. Crie o banco MySQL: `enzo_tech`

### 2. Banco de dados

```bash
cd /www/wwwroot/enzotech.tdesksolutions.com.br
mysql -u USUARIO -p enzo_tech < database.sql
mysql -u USUARIO -p enzo_tech < database_v2.sql
mysql -u USUARIO -p enzo_tech < database_indexes.sql
```

### 3. Configuração

```bash
cd /www/wwwroot/enzotech.tdesksolutions.com.br/config
cp database.local.example.php database.local.php
cp auth.local.example.php auth.local.php
nano database.local.php
```

```php
<?php
return [
    'host' => 'localhost',
    'name' => 'enzo_tech',
    'user' => 'usuario_do_painel',
    'pass' => 'senha_do_banco',
    'debug' => false,
];
```

### 4. Permissões

```bash
cd /www/wwwroot/enzotech.tdesksolutions.com.br
mkdir -p uploads/documentos logs/rate_limit
chown -R www:www uploads logs
chmod -R 775 uploads logs
```

### 5. Nginx

Referência: `deploy/nginx-enzotech.conf`

Socket PHP (aaPanel): `unix:/tmp/php-cgi-82.sock` (ajuste conforme versão).

### 6. SSL

Painel → **SSL** → **Let's Encrypt** → `enzotech.tdesksolutions.com.br`

### 7. DNS

| Tipo | Nome | Valor |
|------|------|--------|
| A | `enzotech` | IP da VPS |

---

## Primeiro acesso

- **https://enzotech.tdesksolutions.com.br/**
- Login: `admin` / `enzo@2025`
- Altere a senha em **Usuários → Alterar senha**

---

## Atualizar

```bash
cd /www/wwwroot/enzotech.tdesksolutions.com.br
git pull origin main
```

Não sobrescreva `config/*.local.php`, `uploads/` nem `logs/`.
