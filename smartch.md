# Documentation du Thème RemUI Customisé pour la FFF

## Vue générale

Le thème **RemUI** de cette installation Moodle est une version entièrement customisée pour la **Fédération Française de Football (FFF)**. Il s'agit d'un thème basé sur le thème RemUI original, mais avec des modifications substantielles pour répondre aux besoins spécifiques de la plateforme de formation de la FFF.

## Architecture du thème

### Structure des dossiers

```
theme/remui/
├── views/           # Logique métier et vues personnalisées (CŒUR DU SYSTÈME)
├── templates/       # Templates Mustache modifiés pour l'interface
├── layout/          # Layouts PHP personnalisés  
├── javascript/      # Scripts JS personnalisés
├── scss/           # Styles SCSS
├── pix/            # Images et ressources
├── lang/           # Fichiers de langue
├── classes/        # Classes PHP du thème
└── config.php      # Configuration principale
```

## 1. Dossier `views/` - Le cœur du système

Le dossier `views/` contient **des fichiers PHP** qui constituent la logique métier principale de la plateforme. Ces fichiers gèrent toutes les fonctionnalités spécifiques à la FFF.

### Fichiers principaux :

#### `utils.php` (3663 lignes)
- **Fonctions de gestion des rôles** : `getMainRole()`, `isAdmin()`, `isAdminFormation()`
- **Gestion des équipes** : fonctions pour les groupes et cohortes
- **Statistiques et rapports** : génération CSV/XLS, graphiques
- **Fonctions d'interface** : `displayHeaderActivity()`, `displayNotification()`
- **Gestion des parcours** : calculs de progression, complétion

#### Vues d'administration :
- `adminformations.php` - Gestion des formations
- `adminteams.php` / `adminteam.php` - Gestion des équipes
- `adminusers.php` / `adminuser.php` - Gestion des utilisateurs
- `viewstats.php` - Statistiques globales

#### Vues pédagogiques :
- `courses_modules.php` - Affichage des modules de cours
- `formation.php` / `formation_student.php` - Interface formations
- `profile.php` - Profil utilisateur
- `sessions.php` - Gestion des sessions

#### Vues spécialisées :
- `calendar.php` - Calendrier des formations
- `cohorts.php` / `cohortmembers.php` - Gestion des cohortes
- `support.php` - Support utilisateur
- `sso.php` - Authentification unique

## 2. Dossier `templates/` - Interface utilisateur

Le dossier contient **plus de 100 templates Mustache** dont de nombreux templates spécifiques à Smartch.

### Templates Smartch principaux :

#### Dashboard :
- `smartch_dashboard_role_*.mustache` - Dashboards par rôle (student, teacher, manager, etc.)
- `smartch_dashboard_my_formations.mustache` - Mes formations
- `smartch_dashboard_stats.mustache` - Statistiques du dashboard

#### Navigation et header :
- `smartch_header_*.mustache` - En-têtes personnalisés
- `smartch_admin_menu.mustache` - Menu administrateur
- `smartch_adminrh_menu.mustache` - Menu RH

#### Cours et formations :
- `smartch_course_*.mustache` - Interface des cours
- `smartch_my_courses.mustache` - Mes cours
- `smartch_calendar.mustache` - Calendrier intégré

#### Interface spécialisée :
- `smartch_teams_header.mustache` - En-tête équipes
- `smartch_pub.mustache` - Publicités/annonces
- `smartch_recommended.mustache` - Recommandations

### Intégration dans les templates principaux :
```mustache
{{> theme_remui/smartch_info }}
{{> theme_remui/smartch_my_courses }}
{{> theme_remui/smartch_dashboard_stats }}
{{> theme_remui/smartch_calendar}}
```

## 3. Dossier `layout/` - Structure des pages

Les layouts définissent la structure des différents types de pages :

- `mypublic.php` (250 lignes) - Layout principal personnalisé
- `frontpage.php` - Page d'accueil
- `course.php` - Pages de cours
- `incourse.php` - Activités dans les cours
- `common.php` - Éléments communs

## 4. Configuration et intégration

### `config.php`
Configuration principale avec :
- Définition des layouts personnalisés
- Intégration JavaScript : `$THEME->javascripts_footer = array('smartch');`
- Configuration SCSS personnalisée

### `lib.php`
Fonctions principales du thème incluant :
- Gestion des fichiers (logos, images)
- Fonctions SCSS
- Fonctions de cache
- Métadonnées de cours personnalisées

## 5. Fonctionnalités spécifiques FFF

### Gestion des rôles
Hiérarchie personnalisée :
1. `super-admin`
2. `manager` 
3. `smalleditingteacher` (Responsable pédagogique)
4. `editingteacher`
5. `teacher`
6. `noneditingteacher`
7. `student`

### Portails multiples
Support de deux portails :
- **Portail Formation** (portailformation)
- **Portail RH** (portailrh)

### Fonctionnalités avancées
- **Statistiques détaillées** : temps passé, progression, scores
- **Gestion d'équipes** : export CSV/XLS, suivi collectif
- **Calendrier intégré** : sessions, plannings
- **Authentification SSO**
- **Support Dropbox** : intégration pour le partage de documents

## 6. Personnalisation visuelle

### JavaScript personnalisé (`smartch.js`)
```javascript
// Effet de transparence sur le header lors du scroll
window.onscroll = function(e) { 
    posY = this.scrollY;
    if (posY > 0)
        document.getElementById('smartch-header').classList.add('partially_transparent');
    else 
        document.getElementById('smartch-header').classList.remove('partially_transparent');
}
```

### Styles SCSS
- Classes personnalisées FFF
- Design responsive
- Thème aux couleurs de la FFF

## 7. Services Web et API

Le thème intègre de nombreux services web personnalisés :
- `theme_remui_get_smartch_my_formations`
- `theme_remui_get_smartch_my_courses`
- `theme_remui_get_smartch_calendar`
- `theme_remui_get_smartch_role`

## 8. Architecture technique

### Points d'intégration principaux :

1. **common_start.mustache** : Point d'entrée principal qui inclut tous les templates Smartch
2. **navbar.mustache** : Navigation personnalisée avec menu administratif
3. **views/utils.php** : Bibliothèque de fonctions métier
4. **config.php** : Configuration et définition des layouts

### Flux de données :
```
Layout PHP → Template Mustache → Services Web → Views PHP → Base de données
```

## 9. Maintenance et évolution

### Points d'attention :
- Les modifications sont concentrées dans `views/` et `templates/`
- Les layouts personnalisés étendent les fonctionnalités de base
- Le JavaScript personnalisé est minimal et ciblé
- Configuration centralisée dans `config.php`

### Recommandations :
- Maintenir la séparation entre logique métier (`views/`) et présentation (`templates/`)
- Documenter les nouveaux services web
- Tester les modifications sur les différents rôles utilisateur
- Préserver la compatibilité avec les mises à jour du thème RemUI de base



