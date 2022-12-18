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
