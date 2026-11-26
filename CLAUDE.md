# Bibliothèque — Application de gestion avec agent IA

Application web de gestion de bibliothèque (catalogue, membres, emprunts) avec un assistant conversationnel basé sur l'API Gemini.

## Stack
- PHP 8.5.11, Symfony 8.x, Doctrine ORM
- PostgreSQL 16
- Twig + Symfony UX (Turbo / Stimulus)
- API Gemini (Google) via `GeminiClientService`
- PHPUnit, Panther, PHPStan, PHP-CS-Fixer
- Docker Compose (dev), GitHub Actions (CI)

## Commandes principales
```
docker compose up -d            # environnement local
composer install
php bin/console secrets:set GEMINI_API_KEY   # clé API (coffre Symfony)
php bin/console doctrine:migrations:migrate
symfony serve                   # serveur de dev
php bin/phpunit                 # tests
npm run build                   # assets (Vite → public/build/)
vendor/bin/phpstan analyse      # analyse statique
vendor/bin/php-cs-fixer fix     # style PSR-12
```

## Conventions essentielles
- PSR-12, typage strict (`declare(strict_types=1)`), code en anglais, docs en français.
- Branches : `main` (stable), `feature/<sujet>`, `fix/<sujet>` ; commits en Conventional Commits.
- Logique métier dans les services, contrôleurs minces.
- L'agent IA n'agit jamais directement sur la base : il appelle des outils qui passent par les services métier et vérifient les droits de l'utilisateur connecté.

## Documentation (lire avant de modifier)
- [Vision](docs/00-vision.md) · [Plan](docs/01-plan.md) · [Stratégie](docs/02-strategie.md)
- [Règles métier et conventions](docs/03-regles.md) — **référence pour l'agent**
- [Architecture](docs/04-architecture.md) · [Développement](docs/05-developpement.md)
- [Agent IA](docs/06-agent-ia.md) · [Tests](docs/07-tests.md) · [Validation](docs/08-validation.md)
- [Sécurité](docs/09-securite.md) · [Déploiement](docs/10-deploiement.md) · [Changelog](docs/CHANGELOG.md)

## Règle d'or
Toute modification de règle métier ou de droits met à jour `docs/03-regles.md` et `docs/09-securite.md` dans le même commit.
