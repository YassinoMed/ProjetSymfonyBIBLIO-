# 05 — Développement

## Prérequis
PHP 8.5.11, Composer, Docker + Docker Compose, Symfony CLI, Node 20 (assets).

## Installation
```
git clone <url> bibliotheque && cd bibliotheque
cp .env .env.local              # renseigner DATABASE_URL et ANTHROPIC_API_KEY
docker compose up -d
composer install
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
symfony serve
```

## Commandes utiles
| Commande | Rôle |
|---|---|
| `php bin/console make:entity` | Créer / modifier une entité |
| `php bin/console make:migration` | Générer une migration |
| `php bin/phpunit` | Lancer les tests |
| `vendor/bin/phpstan analyse` | Analyse statique |
| `vendor/bin/php-cs-fixer fix` | Corriger le style |
| `php bin/console debug:router` | Lister les routes |

## Workflow d'une fonctionnalité
1. Créer `feature/<sujet>` depuis `main`.
2. Vérifier/mettre à jour la règle dans `03-regles.md`.
3. Écrire les tests (service puis fonctionnel).
4. Implémenter : entité → service → voter → contrôleur → template.
5. Si l'agent est concerné : outil + mise à jour de `06-agent-ia.md`.
6. Lancer tests, PHPStan, CS-Fixer ; ouvrir la PR ; mettre à jour `CHANGELOG.md`.
