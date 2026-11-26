# Progression — fonctionnalités IA et recherche

> Une tâche = un commit. La boucle prend la première case non cochée.
> Prérequis : phases 0 à 3 du plan (auth, catalogue, emprunts, réservations) déjà réalisées.

## E0 — Vérification des prérequis
- [x] E0.1 Vérifier que existent et sont testés : entités User, Member, Book, Author, Category, Copy, Loan, Reservation ; services de prêt/réservation ; rôles ROLE_MEMBER, ROLE_LIBRARIAN, ROLE_ADMIN. Sinon : STATUT: BLOQUÉ en listant ce qui manque. Ne rien implémenter ici.

> **STATUT: BLOQUÉ** — audit E0.1 effectué le 2026-11-26 ; aucune implémentation métier effectuée.
>
> **Présents et testés** : entité `User` (`src/Entity/User.php`, `tests/Unit/Entity/UserTest.php`), `UserRepository` et migration `app_user` ; rôles `ROLE_MEMBER`, `ROLE_LIBRARIAN` et `ROLE_ADMIN` configurés dans `config/packages/security.yaml`, utilisés par les fixtures et couverts par `tests/Unit/Security/RoleHierarchyTest.php` et `tests/Functional/Security/AccessControlTest.php`.
>
> **Manquants** : entités `Member`, `Book`, `Author`, `Category`, `Copy`, `Loan` et `Reservation` (aucune classe dans `src/Entity`, aucun repository, aucune migration) ; services `LoanService` et `ReservationService` (aucun service métier correspondant dans `src/Service`) ; tests métier de prêt et de réservation.
>
> **Conséquence** : les phases 0 à 3 annoncées comme prérequis (catalogue, emprunts, réservations) ne sont pas réalisées. `DemoLibraryData` ne fournit que des tableaux de démonstration et `AppFixtures` ne crée que des utilisateurs. La suite E1.1 reste bloquée jusqu'à la réalisation de ces prérequis.

## Étape 1 — Client LLM et AgentService de base
- [x] E1.1 `LlmClientInterface` + `OpenAiCompatibleLlmClient` (HttpClient Symfony, POST {AGENT_BASE_URL}/v1/chat/completions, en-tête Bearer AGENT_API_KEY, modèle AGENT_MODEL, timeout 120 s, 1 nouvel essai, exceptions typées : LlmUnavailableException, LlmUnauthorizedException, LlmInvalidResponseException). Variables dans .env.example sans valeur réelle.
- [x] E1.2 `AgentService` sans outils : prompt système dans un fichier dédié (src/Agent/Prompt), historique tronqué aux N derniers messages (paramètre), `ChatController` minimal (POST /chat, utilisateur authentifié, protection CSRF, échappement de la réponse).
- [x] E1.3 Mode dégradé : si le LLM est indisponible, message clair à l'utilisateur et reste de l'application inchangé ; limitation de débit par utilisateur (rate limiter Symfony).
- [x] E1.4 Tests avec MockHttpClient : succès, 401, timeout, JSON invalide, réponse vide.
- [x] E1.5 Journal technique des appels (durée, tokens si fournis, statut), sans contenu des messages ni clé ; mise à jour de docs/05-developpement.md et docs/06-agent-ia.md.

## Étape 3 — Outils de lecture, voters et audit
- [ ] E3.1 Entité `AuditLog` (acteur, outil, paramètres, résultat résumé, origine UI/agent, date) + `AuditLogger` + migration.
- [ ] E3.2 `AgentToolInterface` + `AgentToolRegistry` : définition de chaque outil (nom, description, schéma de paramètres), exécution avec l'utilisateur courant. Protocole d'appel d'outil en JSON strict dans le texte du modèle (le serveur actuel n'a pas de function-calling natif), isolé dans une classe `ToolCallParser` pour migrer plus tard.
- [ ] E3.3 Outils `search_books`, `get_book`, `get_policy` (règles lues depuis la configuration, jamais codées dans le prompt).
- [ ] E3.4 Outils `get_my_loans`, `get_my_reservations` + voters : seul le propriétaire (ou bibliothécaire/admin) y accède ; aucun identifiant d'utilisateur n'est accepté en paramètre pour un membre.
- [ ] E3.5 Boucle d'outils dans `AgentService` : 3 tours maximum, erreurs d'outil renvoyées au modèle sans détail interne, résultats d'outils traités comme données, chaque appel écrit dans `AuditLog`.
- [ ] E3.6 Tests d'autorisation : un membre ne voit pas les emprunts d'un autre, anonyme refusé, identifiant inventé, injection de prompt dans un résumé de livre sans effet, outil inexistant ignoré.
- [ ] E3.7 Mise à jour de docs/06-agent-ia.md (tableau des outils), docs/09-securite.md et docs/08-validation.md.

## Étape 5 — Recherche hybride
### 5a — Plein texte
- [ ] E5.1 Migration : colonne `search_vector` (tsvector) sur Book (titre, résumé, auteurs), configuration 'french', index GIN, mise à jour par trigger ou listener Doctrine.
- [ ] E5.2 `CatalogSearchService::searchFullText` avec classement `ts_rank` et filtres (auteur, catégorie, disponibilité) ; tolérance aux fautes via pg_trgm si simple à ajouter.
- [ ] E5.3 Brancher sur l'outil `search_books` et sur la recherche de l'interface ; fixtures et tests fonctionnels.
### 5b — Vecteurs et fusion
- [ ] E5.4 Activer pgvector (extension dans l'image Docker et migration), entité `BookEmbedding`, dimension configurable.
- [ ] E5.5 `EmbeddingClientInterface` + implémentation HTTP (endpoint d'embeddings configurable) + `FakeEmbeddingClient` pour les tests ; commande d'indexation et message Messenger déclenché à la modification d'un livre. Si aucun endpoint d'embeddings réel n'est disponible, livrer l'interface et le faux client, puis écrire sous la tâche ce qu'il manque.
- [ ] E5.6 Fusion par RRF du plein texte et de la recherche vectorielle ; repli sur le plein texte seul si les embeddings sont indisponibles.
- [ ] E5.7 Jeu d'évaluation de 20 requêtes avec résultats attendus, commande `app:search:evaluate` qui calcule recall@5 et MRR pour : plein texte seul, vecteurs seuls, hybride ; résultats consignés dans docs/08-validation.md.

## Étape 8 — Recommandation, prévision, anomalies
- [ ] E8.1 Commande `app:demo:generate-data` (graine fixe) : 200 membres, 1 500 livres, environ 8 000 emprunts sur 24 mois avec saisonnalité, et 5 anomalies injectées documentées.
- [ ] E8.2 `RecommendationService` : filtrage collaboratif item-item (similarité cosinus sur la matrice membres × livres), exclut les livres déjà empruntés, tests unitaires.
- [ ] E8.3 `DemandForecastService` : prévision mensuelle par catégorie (moyenne saisonnière ou Holt-Winters simple en PHP), erreur (MAPE) mesurée sur les 3 derniers mois des données générées.
- [ ] E8.4 `AnomalyDetectionService` : score robuste (médiane et MAD) sur prolongations, retards et emprunts par jour ; vérifie que les 5 anomalies injectées sont retrouvées.
- [ ] E8.5 Outils d'agent `get_recommendations` (utilisateur courant uniquement) et `get_anomalies_report` (bibliothécaire/admin) : le LLM ne fait que rédiger l'explication à partir de chiffres calculés ; tests d'autorisation.
- [ ] E8.6 Résultats chiffrés (MAPE, taux de détection, exemples de recommandations) dans docs/08-validation.md ; mise à jour de docs/06-agent-ia.md.
