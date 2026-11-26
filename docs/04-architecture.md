# 04 — Architecture

## Entités principales
| Entité | Champs clés | Relations |
|---|---|---|
| `User` | email, roles, mot de passe haché | 1—1 `Member` (si membre) |
| `Member` | numéro, nom, cotisation_jusqu_au, statut | 1—N `Loan`, 1—N `Reservation` |
| `Book` | titre, ISBN, résumé, année | N—N `Author`, N—1 `Category`, 1—N `Copy` |
| `Author` | nom, prénom | N—N `Book` |
| `Category` | nom | 1—N `Book` |
| `Copy` | code-barres, statut, état | N—1 `Book`, 1—N `Loan` |
| `Loan` | date_début, échéance, date_retour, prolongations | N—1 `Member`, N—1 `Copy` |
| `Reservation` | date, statut, position | N—1 `Member`, N—1 `Book` |
| `Penalty` | montant, statut (due/payée) | N—1 `Loan` |
| `AuditLog` | acteur, outil, paramètres (JSON), résultat résumé, origine (`UI`/`agent`), date | — |
| `ChatConversation` / `ChatMessage` | utilisateur, contenu, date | 1—N |

## Couches
```
Controller / ChatController
        │
        ▼
 Services métier  ◄────────── AgentToolRegistry ◄── AgentService ──► API Gemini
 (LoanService, ReservationService, CatalogService)        │
        │                                                  ▼
        ▼                                            AuditLogger
 Repositories (Doctrine) ──► PostgreSQL
```
Les voters Symfony protègent chaque service sensible ; ils sont appliqués que l'appel vienne de l'UI ou de l'agent.

## Flux de l'agent
1. L'utilisateur envoie un message à `ChatController` (authentifié).
2. `AgentService` construit la requête : prompt système + historique + définitions d'outils.
3. Gemini répond par du texte ou une demande d'outil.
4. `AgentToolRegistry` exécute l'outil **avec l'identité de l'utilisateur** (vérification des droits).
5. Outil de lecture : résultat renvoyé à Gemini. Outil d'écriture : proposition renvoyée à l'utilisateur pour **confirmation**, exécution seulement après son accord.
6. La réponse finale est affichée ; l'action est consignée dans `AuditLog`.

## Structure du code
```
src/
  Controller/  Entity/  Repository/
  Service/            # LoanService, ReservationService, ...
  Security/Voter/
  Agent/              # AgentService, Tool/*, Prompt/
templates/  migrations/  tests/  docs/
```
