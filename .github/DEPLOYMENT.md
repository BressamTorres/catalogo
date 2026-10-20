# CI/CD simplificado do Catálogo Online

## Ambiente

- Produção permanece em `/var/www/catalogo`.
- O Nginx continua apontando para `/var/www/catalogo/public`.
- PHP 8.4 e `php8.4-fpm`.
- CI com PHP 8.4, Node.js 22, Composer 2, Laravel 13, PHPUnit 12 e Vite 8.
- Testes usam SQLite em memória para as conexões principal e `erp_read`.

Não é necessário criar `current`, `releases` ou `shared` nesta etapa.

## Funcionamento

O workflow **CI** roda em pull requests e pushes para `main`. Ele valida Composer e Pint, executa os testes Laravel e compila o frontend.

O workflow **Deploy de produção** é iniciado manualmente e:

1. Confirma que o mesmo commit possui CI aprovado em `main`.
2. Instala dependências PHP sem pacotes de desenvolvimento e compila o frontend no runner.
3. Envia um pacote sem `.env`, `storage`, testes, `.git` ou `node_modules`.
4. Extrai o pacote em `/var/www/catalogo/.deploy/staging`.
5. Cria um snapshot do código atual em `/var/www/catalogo/.deploy/backups`.
6. Coloca o Laravel brevemente em manutenção e sincroniza o código.
7. Preserva `.env`, `storage`, `public/storage`, `.git`, `node_modules` e `.deploy`.
8. Recria os caches, recarrega o PHP-FPM, retira a manutenção e executa o healthcheck.
9. Restaura automaticamente o snapshot se qualquer etapa falhar.

Nenhuma migration é executada. Backups antigos não são removidos automaticamente.

## Secrets do ambiente `production`

Cadastre em **Settings > Environments > production**:

- `DEPLOY_HOST`: hostname ou IP da VPS.
- `DEPLOY_PORT`: porta SSH, normalmente `22`.
- `DEPLOY_USER`: `deploy`.
- `DEPLOY_SSH_KEY`: chave privada exclusiva do GitHub Actions.
- `DEPLOY_KNOWN_HOSTS`: chave pública SSH validada da VPS.
- `DEPLOY_HEALTHCHECK_URL`: URL pública fixa `https://SEU_DOMINIO/health`.

Configure um **Required reviewer** no ambiente `production`, quando disponível no plano do repositório. O deploy permanece manual mesmo sem essa proteção adicional.

## Preparação mínima da VPS

Confirme os comandos necessários:

```bash
php -v
php -m | grep -E 'bcmath|gd|intl|mbstring|pdo_mysql|zip'
command -v curl rsync tar
systemctl status php8.4-fpm
```

O usuário `deploy` deve ser proprietário do projeto e compartilhar o grupo do PHP-FPM:

```bash
sudo chown -R deploy:www-data /var/www/catalogo
sudo chmod 0640 /var/www/catalogo/.env
sudo find /var/www/catalogo/storage /var/www/catalogo/bootstrap/cache -type d -exec chmod 2775 {} \;
sudo find /var/www/catalogo/storage /var/www/catalogo/bootstrap/cache -type f -exec chmod 0664 {} \;
sudo -u deploy mkdir -p /var/www/catalogo/.deploy/staging /var/www/catalogo/.deploy/backups
sudo chmod -R 0750 /var/www/catalogo/.deploy
```

Crie `/usr/local/sbin/reload-php-fpm` como `root`:

```sh
#!/bin/sh
exec systemctl reload php8.4-fpm
```

Defina proprietário `root:root`, permissão `0755` e autorize somente esse comando no `sudoers`:

```text
deploy ALL=(root) NOPASSWD: /usr/local/sbin/reload-php-fpm
```

Adicione a chave pública de deploy em `/home/deploy/.ssh/authorized_keys`. Gere `DEPLOY_KNOWN_HOSTS` a partir de uma máquina confiável e confira o fingerprint da VPS antes de cadastrar o secret. Para porta diferente de 22, a entrada deve identificar `[host]:porta`.

`public/storage` está ausente atualmente. O workflow preserva esse estado e não cria o link automaticamente. Caso uploads públicos sejam adotados futuramente, execute `php artisan storage:link` de forma planejada.

O endpoint `GET /health` retorna somente `{"status":"ok"}` e não depende de empresa, autenticação, sessão, banco de dados ou API do Mini ERP.

## Ativar um deploy

1. Faça push para `main`.
2. Aguarde o workflow **CI** concluir com sucesso.
3. Abra **Actions > Deploy de produção > Run workflow**.
4. Selecione `main` e aprove o ambiente `production`.
5. Confira no log o SHA publicado e o healthcheck.

Deploys simultâneos do Catálogo são serializados pelo GitHub Actions.

## Recuperação manual

O rollback automático já é executado em caso de falha. Para recuperar manualmente, liste os snapshots:

```bash
ls -lah /var/www/catalogo/.deploy/backups
cat /var/www/catalogo/.deploy/backups/ID_DO_BACKUP/REVISION
```

Depois sincronize o snapshot escolhido, preservando os dados persistentes:

```bash
catalog_backup_path=/var/www/catalogo/.deploy/backups/ID_DO_BACKUP
cd /var/www/catalogo
php artisan down --retry=60 --no-interaction
rsync --archive --delete-after \
  --exclude=.deploy \
  --exclude=.env \
  --exclude=.git \
  --exclude=node_modules \
  --exclude=public/storage \
  --exclude=storage \
  "$catalog_backup_path/" /var/www/catalogo/
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
sudo /usr/local/sbin/reload-php-fpm
php artisan queue:restart --no-interaction
php artisan up --no-interaction
```

Valide a URL configurada em `DEPLOY_HEALTHCHECK_URL`. Remova snapshots antigos somente depois de confirmar o funcionamento e possuir backup externo.

## Logs

- GitHub Actions: abra o workflow e a etapa que falhou.
- Laravel: `/var/www/catalogo/storage/logs`.
- Nginx/PHP-FPM: `journalctl` ou os arquivos configurados na VPS.
- Commit publicado: `/var/www/catalogo/REVISION`.
- Integração: confira `ERP_DB_*`, `MINI_ERP_API_URL` e `CATALOG_CREDENTIAL_KEY` no `.env`, sem imprimir seus valores.

Nunca armazene `.env`, senhas, tokens ou chaves privadas no repositório.
