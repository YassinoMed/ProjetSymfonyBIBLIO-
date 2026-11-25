# 01 — Plan

> Les durées sont des estimations indicatives à ajuster.

## Phases et jalons

| Phase | Contenu | Jalon de sortie | Durée |
|---|---|---|---|
| **0. Fondations** | Projet Symfony, Docker, CI, authentification et rôles | Connexion par rôle fonctionnelle, CI verte | 1 sem. |
| **1. Catalogue (CRUD)** | Livres, auteurs, catégories, exemplaires, recherche | Catalogue consultable et administrable | 2 sem. |
| **2. Membres et emprunts** | Inscription, prêts, retours, prolongations, retards, pénalités | Cycle d'emprunt complet testé | 3 sem. |
| **3. Réservations** | File d'attente, notifications | Réservation d'un livre indisponible | 1 sem. |
| **4. Agent IA (lecture)** | `AgentService`, outils de consultation, prompt système | L'agent répond sur catalogue et emprunts du membre | 2 sem. |
| **5. Agent IA (actions)** | Outils d'écriture avec confirmation, journal d'audit | Prolongation / réservation via le chat, confirmées | 2 sem. |
| **6. Chat UI** | Interface de chat, streaming, historique | Chat intégré à l'application | 1 sem. |
| **7. Durcissement et recette** | Sécurité, tests de charge, recette | Checklist de `08-validation.md` validée | 2 sem. |

## Priorités
1. **Indispensable** : catalogue, emprunts/retours, rôles, sécurité.
2. **Important** : réservations, agent en lecture.
3. **Souhaitable** : agent avec actions, notifications, statistiques.

## Dépendances
- L'agent (phases 4-5) dépend des services métier des phases 1-3 : il ne les réimplémente pas.
- Les règles de `03-regles.md` doivent être figées avant la phase 2.
