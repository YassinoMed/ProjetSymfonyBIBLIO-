# 02 — Stratégie

## Choix techniques
| Sujet | Choix | Justification |
|---|---|---|
| Langage | PHP 8.5.11 | Version courante ; requise (≥ 8.4) par Symfony 8 |
| Framework | Symfony 8.x | Sécurité, écosystème (Security, Messenger, Doctrine) ; suivre les versions mineures et la politique de support |
| Base de données | PostgreSQL | Intégrité, recherche plein texte, transactions |
| Front | Twig + Turbo/Stimulus | Simplicité, peu de JavaScript à maintenir |
| IA | API Gemini, appel d'outils (function calling) | Actions structurées, contrôle côté serveur |
| Tests | PHPUnit + Panther | Couverture unitaire, fonctionnelle et E2E |

## Approche
- **Incrémentale** : chaque phase livre une version utilisable.
- **Métier d'abord** : l'agent s'appuie sur les services existants, jamais sur du SQL direct.
- **Moindre privilège** : l'agent hérite exactement des droits de l'utilisateur connecté.
- **Confirmation** : toute action qui modifie des données est confirmée par l'utilisateur.

## Risques et parades
| Risque | Parade |
|---|---|
| Injection de prompt (données du catalogue malveillantes) | Contenu des outils traité comme donnée, droits vérifiés côté serveur, pas d'outil sensible sans confirmation |
| Fuite de données entre membres | Filtrage par utilisateur dans chaque outil, tests dédiés |
| Coût / abus de l'API | Quotas par utilisateur, limite de tokens, cache |
| Indisponibilité de l'API Gemini | Application pleinement utilisable sans l'agent (dégradation gracieuse) |
| Dérive des règles entre UI et agent | Une seule source : les services métier + `03-regles.md` |
| Hallucinations de l'agent | Réponses basées sur les résultats d'outils, sinon aveu d'ignorance |
