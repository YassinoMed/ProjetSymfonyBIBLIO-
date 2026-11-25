# 08 — Validation

## Critères d'acceptation

### Catalogue
- [ ] Un bibliothécaire crée, modifie et retire un livre et ses exemplaires.
- [ ] La recherche par titre, auteur ou catégorie renvoie les bons résultats avec la disponibilité.

### Emprunts
- [ ] Un membre ne peut pas dépasser 5 livres empruntés.
- [ ] Un membre bloqué (pénalités ≥ 10 € ou retard > 30 j) ne peut pas emprunter.
- [ ] Une prolongation est refusée si le livre est réservé ou déjà prolongé une fois.
- [ ] La pénalité de retard est calculée correctement et plafonnée.

### Réservations
- [ ] La file d'attente respecte l'ordre d'arrivée.
- [ ] Une réservation non retirée sous 5 jours expire.

### Agent IA
- [ ] Un membre ne peut pas voir les emprunts d'un autre membre via l'agent.
- [ ] L'agent ne modifie aucune donnée sans confirmation explicite.
- [ ] L'agent donne les mêmes réponses que l'interface sur les règles (durée, plafond).
- [ ] Face à une injection de prompt dans un champ du catalogue, l'agent ne change pas de comportement.
- [ ] Si l'API est indisponible, le reste de l'application fonctionne.

### Sécurité
- [ ] Chaque route et chaque outil est protégé par un voter testé.
- [ ] Aucune clé API dans le dépôt ni dans les logs.

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
- [ ] Quotas de l'API Claude configurés, alertes de coût actives
- [ ] Journal d'audit vérifié
