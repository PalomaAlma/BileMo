# BileMo
API Symfony

Projet de formation consistant à développer une API Rest

## Prérequis et installation
- PHP 7.4
- MySQL 5.7
- Composer 2+


Lancer les commandes suivantes après avoir cloné le projet sur votre machine
1. Pour installer les dépendances
```composer install```
2. Pour créer la base de donées (après avoir configurer le fichier .env)
```php/bin console doctrine:database:create```
3. Pour implémenter la base de donnée
```php/bin console make:migration```
puis
```php/bin console doctrine:migrations:migrate```

### Générer les données de test
Une fois que ces étapes ont été réalisés, il vous faudra générer les données de tests avec cette ligne de commande
```php bin\console doctrine:fixtures:load```

### JWT
Il vous faudra enfin générer les clés du bundle JWT (et installer OpenSSL si besoin)
```php bin/console lexik:jwt:generate-keypair```
Puis ajoutez les lignes suiventes dans votre fichier .env pour indiquer à Symfony où se situent ces clés
```JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem```
```JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem```
```JWT_PASSPHRASE=yourPassphrase````
