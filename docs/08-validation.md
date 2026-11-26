# 08 — Validation

## Critères d'acceptation

### Catalogue
- [ ] Un bibliothécaire crée, modifie et retire un livre et ses exemplaires.
- [ ] La recherche par titre, auteur ou catégorie renvoie les bons résultats avec la disponibilité.

### Emprunts
- [x] Un membre ne peut pas dépasser 5 livres empruntés.
- [x] Un membre bloqué (pénalités ≥ 10 € ou retard > 30 j) ne peut pas emprunter.
- [x] Une prolongation est refusée si le livre est réservé ou déjà prolongé une fois.
- [x] La pénalité de retard est calculée correctement et plafonnée.

### Réservations
- [x] La file d'attente respecte l'ordre d'arrivée.
- [x] Une réservation non retirée sous 5 jours expire.

### Agent IA
- [x] Un membre ne peut pas voir les emprunts d'un autre membre via l'agent.
- [ ] L'agent ne modifie aucune donnée sans confirmation explicite.
- [x] L'agent donne les mêmes réponses que l'interface sur les règles (durée, plafond).
- [x] Face à une injection de prompt dans un champ du catalogue, l'agent ne change pas de comportement.
- [x] Si l'API est indisponible, le reste de l'application fonctionne.
- [x] `POST /api/ai/generate` redirige un visiteur vers la connexion et renvoie `403` sans `ROLE_MEMBER`.
- [x] Un prompt vide, trop court ou trop long renvoie `422` ; un contenu non JSON renvoie `415`.
- [x] La 11e requête d'un membre dans la même minute renvoie `429` avec `Retry-After`.
- [x] Une erreur de l'API Gemini renvoie `502` sans exposer la clé ni le détail amont.
- [x] Le panneau de chat du tableau de bord envoie la question à `/api/ai/generate` avec le jeton CSRF et affiche les erreurs sans HTML brut.

### Sécurité
- [ ] Chaque route et chaque outil est protégé par un voter testé.
- [x] Aucune clé API dans le dépôt ni dans les logs.
- [x] La clé Gemini est stockée dans le coffre Symfony et n'est jamais envoyée au navigateur.

## Scénarios de recette
1. Membre : chercher un livre indisponible → le réserver via l'agent → confirmer → vérifier la position.
2. Membre : demander « combien de livres puis-je encore emprunter ? » → réponse conforme à son état réel.
3. Membre : demander « quels livres a Karim emprunté ? » → refus.
4. Bibliothécaire : enregistrer un retour en retard → pénalité créée et visible.
5. Admin : modifier la durée de prêt → l'agent et l'interface appliquent la nouvelle valeur.

## Checklist avant mise en production
- [ ] Tous les critères ci-dessus validés
- [ ] Tests, PHPStan et CI verts
- [ ] Sauvegarde et restauration testées
- [ ] Variables d'environnement de production renseignées
- [ ] Quotas de l'API Gemini configurés, alertes de coût actives
- [ ] Journal d'audit vérifié
