# APDC Merchant API

## Requirement

- MySQL 5.5
- PHP 5.5+
- [Composer](http://getcomposer.org)


## Installation

Go in the root of the project and init composer.

```bash
composer install
```

At the end of the installation, composer will ask you for some informations.
Mainly the information about the database. If you are not sure you can change any informations later in
`app/config/parameters.yml`.

## Import SQL datas

By start, no user are present, you will need to create the first user manualy. Use phpMyAdmin or mysql command.

```sql
INSERT INTO `user` (`id`, `username`, `username_canonical`, `email`, `email_canonical`, `enabled`, `salt`, `password`, `last_login`, `locked`, `expired`, `expires_at`, `confirmation_token`, `password_requested_at`, `roles`, `credentials_expired`, `credentials_expire_at`, `first_name`, `last_name`, `shop_name`, `address`, `zip`, `city`, `mobile`, `phone`) VALUES
(1, 'admin', 'admin', 'admin@local.lan', 'admin@local.lan', 1, '4lcqep7fuf8k8044ggg4koo8cgggoss', '$2y$13$Tp/Z/GcMJmVF8xHPNm4tbuW1P6vSgMdnMU21FVsJNzEotlgbjIIjK', NULL, 0, 0, NULL, NULL, NULL, 'a:1:{i:0;s:10:"ROLE_ADMIN";}', 0, NULL, 'Admin', 'APDC', 'APDC', 'Address', '75000', 'Paris', '0000000', '');
```

Then you need to create the OAuth client.

```sql
INSERT INTO `client` (`id`, `random_id`, `redirect_uris`, `secret`, `allowed_grant_types`) VALUES
(1, '3bcbxd9e24g0gk4swg0kwgcwg4o8k8g4g888kwc44gcc0gwwk4', 'a:0:{}', '4ok2x70rlfokc8g0wws8c8kwcokw80k44sg48goc0ok4w0so0k', 'a:1:{i:0;s:8:"password";}');
```

If you are working in production, you better change the user password and client secret.

## Working in development

If you are working on development env, you will need to edit `web/.htaccess` to enabled it.
And change:

```
RewriteRule ^ %{ENV:BASE}/app.php [L]
```

to

```
RewriteRule ^ %{ENV:BASE}/app_dev.php [L]
```

PS: Do not commit this file.