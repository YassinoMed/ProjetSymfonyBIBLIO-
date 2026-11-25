# 03 — Règles

## 1. Règles métier
> Valeurs par défaut à valider. Elles doivent être configurables (paramètres), jamais codées en dur.

| Règle | Valeur par défaut |
|---|---|
| Durée d'emprunt | 21 jours |
| Maximum de livres empruntés simultanément | 5 par membre |
| Prolongations | 1 maximum, de 14 jours, si aucune réservation en attente |
| Pénalité de retard | 0,20 € par jour et par livre, plafonnée à 10 € par livre |
| Blocage de prêt | Dès 10 € de pénalités impayées ou 1 livre en retard de plus de 30 jours |
| Réservations | 3 maximum par membre ; retrait disponible sous 5 jours |
| Livre non empruntable | Exemplaire marqué « consultation sur place », perdu ou en réparation |
| Cotisation | Membre actif requis (cotisation à jour) pour emprunter |

### Statuts
- **Exemplaire** : `disponible`, `emprunté`, `réservé`, `en_réparation`, `perdu`
- **Emprunt** : `en_cours`, `rendu`, `en_retard`
- **Réservation** : `en_attente`, `disponible`, `retirée`, `expirée`, `annulée`

## 2. Conventions de code
- PSR-12, `declare(strict_types=1)`, PHPStan niveau 6 minimum.
- Code, classes et variables en anglais ; documentation et messages utilisateur en français.
- Nommage : entités au singulier (`Book`, `Loan`), services `XxxService`, voters `XxxVoter`.
- Contrôleurs minces ; logique dans `src/Service`, droits dans `src/Security/Voter`.
- Aucune requête SQL brute sans paramètres liés.

## 3. Git
- Branches : `main`, `feature/<sujet>`, `fix/<sujet>`.
- Commits : Conventional Commits (`feat:`, `fix:`, `docs:`, `test:`, `refactor:`).
- Pull request obligatoire, CI verte, une relecture minimum.
- Pas de secret dans le dépôt (`.env.local` ignoré).

## 4. Règle de cohérence
Toute évolution d'une règle métier : mise à jour de ce fichier, des tests et de `06-agent-ia.md` dans le même commit.
