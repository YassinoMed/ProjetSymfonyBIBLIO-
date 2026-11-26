# 10 — Déploiement

## Environnements
| Environnement | Usage | Particularités |
|---|---|---|
| `dev` | Développement local | Docker Compose, fixtures, debug activé |
| `test` | CI | Base éphémère, client Gemini mocké |
| `staging` | Recette | Données anonymisées, clé API dédiée |
| `prod` | Production | `APP_ENV=prod`, debug désactivé, HTTPS |

## Variables d'environnement
| Variable | Description |
|---|---|
| `APP_ENV`, `APP_SECRET` | Environnement et secret Symfony |
| `DATABASE_URL` | Connexion PostgreSQL |
| `GEMINI_API_KEY` | Clé Gemini, stockée dans le coffre Symfony (`secrets:set`) |
| `MAILER_DSN` | Envoi d'emails (notifications) |

Les quotas de l'assistant sont configurés dans `config/packages/rate_limiter.yaml` (10 requêtes/minute par membre).

Les secrets de production sont gérés par un coffre de secrets (ou `secrets:set` de Symfony), jamais commités.

## CI/CD (GitHub Actions)
1. PR : installation, PHPStan, CS-Fixer, PHPUnit.
2. Fusion sur `main` : construction de l'image Docker, déploiement automatique en `staging`.
3. Tag de version : déploiement en `prod` après validation manuelle.
4. Migrations exécutées au déploiement (`doctrine:migrations:migrate --no-interaction`) ; retour arrière documenté.

## Sauvegardes
- Dump PostgreSQL quotidien, rétention 30 jours, copie hors site.
- Test de restauration mensuel.

## Supervision
Logs applicatifs centralisés, alertes sur erreurs 5xx, échecs de l'API Gemini et dépassement de coût.
