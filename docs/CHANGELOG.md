# Changelog

Format : [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) — versionnage sémantique.

## [Non publié]
### Ajouté
- Documentation initiale du projet (`docs/00` à `docs/10`, `CLAUDE.md`).
- Audit E0.1 : prérequis métier manquants (`Member`, `Book`, `Author`, `Category`, `Copy`, `Loan`, `Reservation` et services de prêt/réservation) consignés dans `docs/PROGRESS.md` sous `STATUT: BLOQUÉ` ; aucune implémentation.
- Intégration Gemini sécurisée : `GeminiClientService` + `GeminiClientInterface`, endpoint `POST /api/ai/generate`, DTO `PromptRequestDto` validé, limite de 10 requêtes/minute par membre, tests unitaires et fonctionnels.
- Interface de chat du tableau de bord reliée à l'endpoint réel (fetch same-origin, jeton CSRF, indicateur de rédaction, gestion des erreurs `403`/`429`/`502`) ; les réponses simulées sont supprimées.
- Coffre Symfony Secrets pour `GEMINI_API_KEY` ; clés privées du coffre ignorées par Git.
- Dépendances `symfony/http-client`, `symfony/rate-limiter`, `symfony/serializer`.
- Client LLM compatible OpenAI (`E1.1`) : `App\Agent\LlmClientInterface`, `App\Agent\OpenAiCompatibleLlmClient` (timeout 120 s, 1 nouvelle tentative), exceptions `LlmUnavailableException`, `LlmUnauthorizedException`, `LlmInvalidResponseException`, variables `AGENT_BASE_URL`, `AGENT_API_KEY`, `AGENT_MODEL` dans `.env.example` / `.env` / `.env.test`, tests unitaires avec `MockHttpClient`.
- Socle `AgentService` et `POST /chat` (`E1.2`) : prompt système dédié (`src/Agent/Prompt/system_prompt.txt` + `SystemPromptProvider`), troncature de l'historique aux `N` derniers messages (`app.agent.max_history_messages`), `ChatController` (`ROLE_MEMBER`, protection CSRF, validation `ChatRequestDto`, échappement HTML de la réponse), tests unitaires et fonctionnels.
- Mode dégradé et limitation de débit sur `/chat` (`E1.3`) : limiteur `agent_chat` (`10` requêtes/minute par utilisateur, `429` + `Retry-After`) et réponse `503` claire en cas d'indisponibilité du LLM sans fuite d'erreur interne.
- Tests d'intégration `MockHttpClient` (`E1.4`) : `tests/Unit/Agent/AgentMockHttpClientTest.php` couvrant la chaîne `AgentService` + `OpenAiCompatibleLlmClient` sur les cas succès, `401`, timeout, JSON invalide et réponse vide.
- Journalisation technique des appels LLM (`E1.5`) : enregistrement de `duration_ms`, `http_status`, `status`, `attempt` et compteurs `usage` optionnels dans `OpenAiCompatibleLlmClient` sans contenu ni clé API ; mise à jour de `docs/05-developpement.md` et `docs/06-agent-ia.md`.

### Sécurité
- La clé Gemini n'est jamais écrite dans `.env`, les logs ou le navigateur ; elle transite par l'en-tête `x-goog-api-key`.
- Les erreurs de l'API amont sont journalisées côté serveur (statut et type d'exception uniquement) et remplacées par des messages génériques côté client.

### Audit prérequis
- E0.1 : statut BLOQUÉ — entités `Member`, `Book`, `Author`, `Category`, `Copy`, `Loan`, `Reservation` et services de prêt/réservation absents ; phases 0 à 3 à réaliser avant l'étape 1.
