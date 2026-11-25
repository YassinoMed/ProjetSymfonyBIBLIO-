# 00 — Vision

## Objectif
Offrir à une bibliothèque un outil simple pour gérer son catalogue, ses membres et ses emprunts, avec un assistant conversationnel qui répond aux questions et exécute des actions encadrées.

## Utilisateurs cibles
| Rôle | Besoins principaux |
|---|---|
| **Membre** | Rechercher des livres, voir ses emprunts, réserver, prolonger, poser des questions à l'assistant |
| **Bibliothécaire** | Gérer le catalogue, enregistrer prêts et retours, suivre les retards, gérer les membres |
| **Administrateur** | Gérer les comptes et rôles, paramètres des règles, journal d'audit |

## Périmètre (inclus)
- Catalogue (livres, auteurs, catégories, exemplaires)
- Membres et authentification par rôle
- Emprunts, retours, prolongations, réservations, pénalités
- Agent IA : recherche, consultation, actions confirmées

## Hors périmètre (v1)
- Paiement en ligne des pénalités
- Application mobile native
- Prêt entre bibliothèques
- Livres numériques

## Critères de réussite
- Un membre trouve un livre et connaît sa disponibilité en moins de 3 interactions.
- L'agent ne divulgue jamais de données d'un autre membre.
- Les règles métier sont appliquées de façon identique via l'interface et via l'agent.
