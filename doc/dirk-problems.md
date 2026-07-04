# Détails des problèmes :
Dans mon permiers commit j'ai trouvée que app/Paths a été mal configuré

[Sat Jul  4 11:57:49 2026] [::1]:51500 [500]: GET / - Uncaught Error: Failed opening required '/home/tomefy/Documents/sig/sig-projet/app/Config/../../system/Boot.php' (include_path='.:/usr/share/pear:/usr/share/php') in /home/tomefy/Documents/sig/sig-projet/public/index.php:57


# Étapes pour fixer cela :
- Installer composer et puis installer les dépendance CI4
```sh
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

sudo mv composer.phar /usr/local/bin/composer

sudo chmod +x /usr/local/bin/composer

composer --version

composer install
```
- Changer le path de systemDirectory
```php
//Change la valeur de la
//  variable vers celui ci pour pointer vers
//  le système de votre vendor actuelle
public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';
```