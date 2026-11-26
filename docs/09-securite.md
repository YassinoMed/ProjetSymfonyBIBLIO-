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
- L'endpoint IA exige le jeton `ai_generate` dans l'en-tête `X-CSRF-Token` ; sans jeton valide, il répond `403` avant de consommer le quota.
- Minimisation : seules les données nécessaires sont envoyées à l'API Gemini (pas d'email ni d'adresse).
- Conformité RGPD : finalité, durée de conservation de l'historique de chat, droit d'accès et d'effacement.

## Clé API (Gemini)
- Stockée dans le coffre **Symfony Secrets** (`php bin/console secrets:set GEMINI_API_KEY`), jamais dans `.env`, le code ou un template.
- Les clés privées du coffre (`*.decrypt.private.php`) sont ignorées par Git ; en production, la clé de déchiffrement est fournie via `SYMFONY_DECRYPTION_SECRET`.
- La clé circule uniquement dans l'en-tête `x-goog-api-key` du serveur vers Google : jamais dans une URL, un log ou le navigateur.
- Toute clé exposée est révoquée immédiatement et remplacée ; clé distincte par environnement ; restriction de la clé à la Generative Language API et, si possible, à l'IP du serveur.
- Aucun appel direct depuis Twig ou JavaScript vers Google : le navigateur appelle uniquement `POST /api/ai/generate`, qui ajoute la clé côté serveur.
- Le profiler Symfony (dev uniquement) capture les appels HTTP : ne jamais l'exposer publiquement et le laisser désactivé en production.

## Limites d'usage
- 10 générations par minute et par membre sur `/api/ai/generate` (`sliding_window`), réponse `429` avec `Retry-After`.
- Limitation de débit également sur la connexion, plafond de tokens et taille maximale des messages.
- En production multi-instances, utiliser un cache partagé (Redis) pour `cache.rate_limiter` afin que la limite soit globale et non par serveur.

## Injection de prompt
- Les résultats d'outils sont traités comme des données non fiables.
- Les droits ne dépendent jamais du texte généré par le modèle : ils sont vérifiés par le code.
- Les actions d'écriture exigent une confirmation de l'utilisateur, affichée par l'interface (pas par le modèle).
- Aucun outil ne permet d'exécuter du code, d'accéder au système de fichiers ou d'appeler des URL arbitraires.
- Les réponses du modèle sont insérées avec `textContent` (pas de HTML brut) et jamais interprétées comme des instructions.

## Journalisation
Chaque action de l'agent est consignée (acteur, outil, paramètres, résultat, date). Les logs ne contiennent ni mots de passe, ni clé API, ni prompt utilisateur : seuls le statut HTTP et le type d'exception sont journalisés côté serveur.
