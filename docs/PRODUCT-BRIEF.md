# Product Brief — Expérience Bibliothèque

## Diagnostic

### 1. Pour qui ?

- **Visiteur** qui cherche une œuvre, consulte sa disponibilité ou découvre le service.
- **Membre** qui gère ses emprunts, réservations et prolongations, et interroge l’assistant.
- **Bibliothécaire** qui suit les prêts, retards, réservations et opérations du catalogue.
- **Administrateur** qui contrôle les rôles, les paramètres et le journal d’audit.

### 2. Quel problème ?

La gestion de bibliothèque est répartie entre consultation, gestion des prêts et assistance conversationnelle. Le membre doit aujourd’hui comprendre où trouver l’information, et le personnel doit réconcilier plusieurs vues opérationnelles. Le coût principal est le temps de recherche et le risque d’erreur lors d’une action.

### 3. Pourquoi maintenant ?

Le socle Symfony 8, Doctrine et les règles métier sont documentés. Un système visuel unifié et une couche d’interaction permettent maintenant de valider les parcours avant de figer les modèles de données et l’intégration de l’agent IA.

### 4. Version 10 étoiles ?

Un assistant问到 qui comprend à la fois le catalogue et la situation personnelle de l’utilisateur, propose la prochaine action, l’exécute après confirmation et journalise chaque opération. Tous les rôles partagent le même langage visuel et les mêmes règles.

### 5. MVP de ce lot

- Accueil public éditorial et informatif.
- Page de connexion et navigation vers chaque espace.
- Espaces membre, bibliothécaire et administrateur.
- Catalogue avec recherche, filtres et états de disponibilité.
- Quatre directions visuelles activables sans rechargement.
- Motion GSAP : révélations, parallaxe, compteurs, hover et transitions de dashboard.
- Interactions de démonstration : filtres, recherche, onglets, chat simulé, notifications et menu mobile.
- Accessibilité : navigation clavier, focus visible,_landmarks, libellés, contraste et prise en charge de `prefers-reduced-motion`.

### 6. Anti-goal

Ce lot ne constitue pas le produit métier complet. Il ne remplace ni le catalogue Doctrine, ni les règles d’emprunt, ni l’assistant Gemini, ni l’authentification de production. Les données affichées sont des fixtures de démonstration.

### 7. Comment mesurer la réussite ?

- Un visiteur atteint une suggestion de livre en moins de trois interactions.
- Un membre voit immédiatement ses emprunts, échéances et reservations.
- Un bibliothécaire repère les retards et actions urgentes sans changer de page.
- La page reste lisible à 390 px et 1440 px.
- Les animations sont supprimées ou neutralisées selon les préférences système.
- Le socle visuel permet d’ajouter les écrans réels sans refaire la direction artistique.

## Risques principaux

- **Dépendance au statut de démonstration** : les libellés et actions doivent devenir adaptatifs lorsque les services métier seront branchés.
- **Multiplicité des thèmes** : les quatre variantes reposent sur les mêmes tokens sémantiques afin d’éviter quatre systèmes parallèles.
- **Motion excessif** : les animations sont cantonnées aux transitions, révélations et retours d’action.
- **Faux sentiment de sécurité** : les espaces de démonstration n mesmerment pas de contrôle d’accès réel.

## Recommandation

**GO pour le vertical slice d’expérience et de design system.** Brancher ensuite la persistance et l’agent par phases, en conservant les contrats d’interface déjà visibles.
