# 09 — Sécurité

## Rôles et droits
| Action | Visiteur | Membre | Bibliothécaire | Admin |
|---|---|---|---|---|
| Consulter le catalogue | ✔ | ✔ | ✔ | ✔ |
| Voir ses emprunts / réservations | — | ✔ | ✔ | ✔ |
| Réserver, prolonger (les siens) | — | ✔ | ✔ | ✔ |
| Voir les emprunts de tous | — | — | ✔ | ✔ |
| Enregistrer prêts et retours | — | — | ✔ | ✔ |
| Gérer le catalogue | — | — | ✔ | ✔ |
| Gérer comptes, rôles, paramètres | — | — | — | ✔ |
| Consulter le journal d'audit | — | — | — | ✔ |

Implémentation : Symfony Security + voters. Les droits sont vérifiés côté serveur, y compris pour les appels de l'agent.

## Protection des données
- Mots de passe hachés (algorithme `auto` de Symfony), HTTPS obligatoire, cookies `Secure` et `HttpOnly`.
- Protection CSRF sur tous les formulaires, validation stricte des entrées.
- Minimisation : seules les données nécessaires sont envoyées à l'API Claude (pas d'email ni d'adresse).
- Conformité RGPD : finalité, durée de conservation de l'historique de chat, droit d'accès et d'effacement.

## Clé API
- Stockée en variable d'environnement ou coffre de secrets, jamais dans le dépôt.
- Rotation périodique ; clé distincte par environnement ; jamais exposée au navigateur.
- Alertes sur la consommation.

## Limites d'usage
Limitation de débit par utilisateur (chat et connexion), plafond de tokens, taille maximale des messages.

## Injection de prompt
- Les résultats d'outils sont traités comme des données non fiables.
- Les droits ne dépendent jamais du texte généré par le modèle : ils sont vérifiés par le code.
- Les actions d'écriture exigent une confirmation de l'utilisateur, affichée par l'interface (pas par le modèle).
- Aucun outil ne permet d'exécuter du code, d'accéder au système de fichiers ou d'appeler des URL arbitraires.
- Les réponses du modèle sont échappées avant affichage (pas de HTML brut).

## Journalisation
Chaque action de l'agent est consignée (acteur, outil, paramètres, résultat, date). Les logs ne contiennent ni mots de passe ni clé API.
