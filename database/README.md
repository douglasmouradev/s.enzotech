# Banco de dados — Enzo Tech

## Instalação nova

Execute na ordem (PowerShell):

```powershell
Get-Content database.sql -Raw | mysql -u root -p
Get-Content database_v2.sql -Raw | mysql -u root -p
Get-Content database_indexes.sql -Raw | mysql -u root -p
```

`database_lgpd.sql` é legado — as colunas LGPD já estão em `database.sql`. Use apenas se estiver migrando de uma versão muito antiga.

## Arquivos

| Arquivo | Uso |
|---------|-----|
| `database.sql` | Schema base + LGPD + seeds |
| `database_v2.sql` | Multi-usuário, vendas v2, celulares v2 |
| `database_indexes.sql` | Índices de performance (rodar uma vez) |
| `database_lgpd.sql` | **Legado** — migração incremental antiga |
