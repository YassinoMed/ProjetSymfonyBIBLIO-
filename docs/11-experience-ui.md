# 11 — Expérience UI et motion

## CAPABILITY

Les visiteurs, membres, bibliothécaires et administrateurs disposent d’un socle d’interface Symfony/Twig commun, responsive et accessible, avec quatre expressions visuelles activables et une motion GSAP cohérente. Les parcours de démonstration rendent visibles le catalogue, les emprunts, les retards, les réservations, l’administration et l’assistant avant le branchement des services métier.

## CONTRAINTES

- Twig reste le rendu principal ; aucun framework JavaScript SPA n’est introduit.
- GSAP, ScrollTrigger et Lenis sont compilés par Vite et versionnés avec `package-lock.json`.
- Les quatre directions partagent les mêmes tokens sémantiques et la même structure DOM.
- `prefers-reduced-motion` désactive le smooth scroll et neutralise les animations non essentielles.
- Aucun contenu ni contrôle ne dépend exclusivement de la couleur, du hover ou du mouvement.
- Les données sont des fixtures explicites tant que les entités et services métier ne sont pas disponibles.
- Les espaces de démonstration n’indiquent pas de protection d’accès réelle.

## CONTRAT D’IMPLÉMENTATION

### Acteurs et surfaces

| Surface | Acteur | Action principale |
|---|---|---|
| `/` | Visiteur | Trouver une suggestion ou comprendre le service |
| `/connexion` | Visiteur | Accéder à l’espace de démonstration |
| `/catalogue` | Tous | Rechercher et filtrer une œuvre |
| `/membre` | Membre | Suivre emprunts et réservations, interroger l’assistant |
| `/bibliothecaire` | Bibliothécaire | Traiter retards et opérations en attente |
| `/administrateur` | Administrateur | Contrôler rôles, paramètres et audit |

### États et transitions

- **Navigation** : accueil ↔ catalogue ↔ espaces de démonstration.
- **Recherche** : vide → résultats → aucun résultat ; le compteur et les filtres restent synchronisés.
- **Thème** : thème initial sans flash → thème mémorisé ; changement instantané et annoncé.
- **Chat** : prêt → saisie → indicateur de rédaction → réponse Gemini ou erreur lisible (`403`, `429`, `502`) ; la zone de réponse utilise `aria-live`.
- **Mobile** : navigation ouverte ↔ fermée ; fermeture par bouton, lien ou `Escape`.
- **Motion** : mouvement autorisé ↔ mouvement réduit ; le contenu reste visible dans les deux cas.

### Données et implications

- Fixtures de démonstration dans un service dedié, injecté par autowiring.
- Contrôleurs minces renvoyant les templates et les fixtures.
- Aucun schéma Doctrine ni migration n’est introduit par ce lot.
- Les URLs et conventions de navigation préfigurent les futurs services métier.

### Sécurité et politique

- Aucune donnée personnelle réelle, aucun secret et aucune clé d’API dans le front.
- Les entrées de chat sont rendues comme du texte, jamais comme du HTML.
- Les routes sensibles (`/membre`, `/api/ai/generate`) sont protégées par Security ; les voters restent à ajouter avec le branchement métier.

## NON-GOALS

- Implémentation complète des entités, prêts, retours, pénalités et réservations.
- Connexion réelle, inscription et gestion des comptes.
- Streaming et outils de l’agent (l’appel Gemini de base est implémenté).
- Paiement, notification e-mail et production.
- Application mobile native.

## QUESTIONS OUVERTES

- Quel authentificateur Symfony sera retenu pour la production ?
- Les règles configurables seront-elles gérées par paramètres Doctrine, par configuration applicative ou par une table dédiée ?
- Le catalogue doit-il utiliser PostgreSQL Full Text ou un index applicatif au premier volume ?
- Les quatre thèmes sont-ils tous destinés à la production ou seul « Éditorial » est-il la marque par défaut ?

## HANDOFF

Implémentation directe possible pour le vertical slice. Après validation visuelle, enchaîner avec `product-capability` pour le catalogue métier, puis `tdd-workflow`, `security-review` et `verification-loop`.
