# 05 — Développement

## Prérequis
PHP 8.5.11, Composer, Docker + Docker Compose, Symfony CLI, Node 20 (assets).

## Installation
```
git clone <url> bibliotheque && cd bibliotheque
cp .env .env.local              # renseigner DATABASE_URL
docker compose up -d
composer install
php bin/console secrets:generate-keys        # une seule fois par environnement
php bin/console secrets:set GEMINI_API_KEY   # saisie masquée, jamais dans .env
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
symfony serve
```

Avec Docker, préfixer les commandes par `docker compose exec app` (par exemple
`docker compose exec app php bin/console secrets:set GEMINI_API_KEY`).

### Configuration de l'agent LLM (`AGENT_*`)
Renseigner dans `.env.local` (jamais dans `.env`) les variables de connexion au serveur compatible OpenAI :
- `AGENT_BASE_URL` : URL de base du serveur (ex. tunnel ou instance locale).
- `AGENT_API_KEY` : jeton Bearer (ou coffre Symfony Secrets).
- `AGENT_MODEL` : identifiant du modèle cible.

Les appels LLM produisent des logs techniques (`duration_ms`, `http_status`, `status`, `attempt`, et `prompt_tokens`/`completion_tokens`/`total_tokens` si fournis par l'API), sans jamais enregistrer le contenu des messages ni la clé API.

## Commandes utiles
| Commande | Rôle |
|---|---|
| `php bin/console make:entity` | Créer / modifier une entité |
| `php bin/console make:migration` | Générer une migration |
| `php bin/phpunit` | Lancer les tests |
| `docker compose exec -e APP_ENV=test app php bin/phpunit` | Tests dans Docker (le compose force `APP_ENV=dev`) |
| `vendor/bin/phpstan analyse` | Analyse statique |
| `vendor/bin/php-cs-fixer fix` | Corriger le style |
| `php bin/console debug:router` | Lister les routes |
| `php bin/console debug:router api_ai_generate` | Vérifier l'endpoint IA |
| `npm run build` | Compiler les assets (Vite) vers `public/build/` |

## Workflow d'une fonctionnalité
1. Créer `feature/<sujet>` depuis `main`.
2. Vérifier/mettre à jour la règle dans `03-regles.md`.
3. Écrire les tests (service puis fonctionnel).
4. Implémenter : entité → service → voter → contrôleur → template.
5. Si l'agent est concerné : outil + mise à jour de `06-agent-ia.md`.
6. Lancer tests, PHPStan, CS-Fixer ; ouvrir la PR ; mettre à jour `CHANGELOG.md`.
