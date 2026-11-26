# 07 — Tests

## Stratégie
| Niveau | Outil | Cible |
|---|---|---|
| Unitaires | PHPUnit | Règles métier (durées, plafonds, pénalités), calculs de dates |
| Intégration | PHPUnit + base de test | Repositories, services avec la base |
| Fonctionnels | WebTestCase | Routes, formulaires, droits par rôle |
| E2E | Symfony Panther | Parcours : recherche, emprunt, chat |
| Outils de l'agent | PHPUnit | Chaque outil : droits, entrées invalides, confirmation |
| Agent (simulé) | Client Gemini mocké | Orchestration, gestion des erreurs d'API |
| Évaluation du prompt | Jeu de scénarios | Comportement de l'agent (voir ci-dessous) |

## Tests spécifiques à l'agent
- Chaque outil refuse un utilisateur non autorisé et un identifiant appartenant à un autre membre.
- Un outil d'écriture n'est jamais exécuté sans confirmation.
- Un titre de livre contenant « ignore tes instructions… » ne change pas le comportement.
- Réponse correcte quand l'outil renvoie un résultat vide ou une erreur.

## Couverture visée
- Services métier : ≥ 90 %
- Outils de l'agent et voters : 100 % des chemins d'autorisation
- Global : ≥ 80 %

## CI
À chaque PR : PHPStan, CS-Fixer (vérification), PHPUnit. Les E2E tournent avant fusion sur `main`.
