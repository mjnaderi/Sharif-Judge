# Clean URLs

By default, `index.php` is a part of all urls in Sharif Judge. e.g.

    http://example.mjnaderi.ir/index.php/dashboard
    http://example.mjnaderi.ir/index.php/users/add

You can remove `index.php` and have pretty and nice urls if your system supports rewrite rules:

    http://example.mjnaderi.ir/dashboard
    http://example.mjnaderi.ir/users/add

## Enabling clean urls

Rename file `.htaccess2` located in the main directory of Sharif Judge to `.htaccess`.

This is `.htaccess2` file:

```apache
# You also need to change 
# $config['index_page'] = 'index.php';
# to
# $config['index_page'] = '';
# in application/config/config.php
# in order to enable clean urls.

RewriteEngine on
RewriteCond $1 !^(index\.php|assets|robots\.txt)
RewriteRule ^(.*)$ index.php?/$1 [L]
```

Then open file `application/config/config.php` and change

```php
$config['index_page'] = 'index.php';
```

to

```php
$config['index_page'] = '';
```

If it didn't work, just revert the changes!
