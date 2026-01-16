# Projet MLC 2.0 - Nouvelle Version

## Structure
- Application Symfony avec Tailwind CSS
- Templates Twig dans `app/templates/`
- Entités dans `app/src/Domain/`
- Controllers dans `app/src/Infrastructure/Http/Controller/`

## Entités importantes

### Player (`Domain/Player/Entity/Player.php`)
- `login`, `email`, `pseudo`
- `hasTeam()` : vérifie si le joueur a une équipe active
- `getTeam()` : retourne l'équipe active du joueur

### Team (`Domain/Team/Entity/Team.php`)
- `tag` : abréviation de l'équipe (ex: "MLC")
- `fullName` : nom complet de l'équipe
- `creator` : joueur créateur de l'équipe
- **Attention** : pas de propriété `name`, utiliser `tag` ou `fullName`

## Routes équipe
- `app_team_edit` : page de gestion de l'équipe
- `app_team_create` : création d'équipe
- `app_team_join` : rejoindre une équipe
- `app_team_requests` : demandes d'adhésion

## Fichiers récemment modifiés
- `app/templates/partials/_header.html.twig` : header responsive avec menu hamburger mobile et lien équipe
