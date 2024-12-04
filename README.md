# Maison Provence 

[![PHP Version](https://img.shields.io/badge/PHP-8.2-777BB4.svg?style=flat-square&logo=php)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/Symfony-7.1-000000.svg?style=flat-square&logo=symfony)](https://symfony.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791.svg?style=flat-square&logo=postgresql)](https://www.postgresql.org)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)
[![GitHub Actions](https://img.shields.io/github/actions/workflow/status/bensaadmucret/Maison-Provence/ci.yml?branch=main&style=flat-square)](https://github.com/bensaadmucret/Maison-Provence/actions)
[![Coverage Status](https://coveralls.io/repos/github/bensaadmucret/Maison-Provence/badge.svg?branch=main)](https://coveralls.io/github/bensaadmucret/Maison-Provence?branch=main)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat-square)](https://phpstan.org/)

Une plateforme e-commerce moderne développée avec Symfony 7.1, mettant l'accent sur l'optimisation SEO et la gestion efficace des produits.

## Fonctionnalités

- Gestion complète des produits et catégories
- Optimisation SEO avancée pour chaque entité
- Gestion des médias et images
- Configuration du site centralisée
- Interface d'administration intuitive
- Validation robuste des données
- Tests unitaires complets

## Technologies

- **Framework**: Symfony 7.1
- **Base de données**: PostgreSQL
- **ORM**: Doctrine
- **Tests**: PHPUnit
- **Templates**: Twig
- **Docker**: Environnement de développement conteneurisé

## Prérequis

- Docker et Docker Compose
- PHP 8.2 ou supérieur
- Composer
- Node.js et Yarn (pour les assets)

## Installation

1. Cloner le repository :
```bash
git clone https://github.com/votre-repo/maison-provence.git
cd maison-provence
```

2. Copier le fichier d'environnement :
```bash
cp .env.example .env
```

3. Lancer l'environnement Docker :
```bash
docker-compose up -d
```

4. Installer les dépendances :
```bash
docker-compose exec php composer install
```

5. Créer la base de données et exécuter les migrations :
```bash
docker-compose exec php bin/console doctrine:database:create
docker-compose exec php bin/console doctrine:migrations:migrate
```

6. Charger les fixtures (données de test) :
```bash
docker-compose exec php bin/console doctrine:fixtures:load
```

## CI/CD

Le projet utilise GitHub Actions pour l'intégration continue et les analyses de sécurité.

### Workflows

#### CI (Intégration Continue)
- Exécute les tests PHPUnit
- Vérifie la qualité du code (PHP CS Fixer)
- Analyse statique du code (PHPStan)
- Génère et upload la couverture de code
- Déclenché sur push et pull requests

#### Sécurité
- Analyse de sécurité hebdomadaire
- Vérification des dépendances
- Audit de sécurité du code
- Scan Snyk
- Génération de rapports de sécurité

### Configuration requise
- SNYK_TOKEN (optionnel, pour les scans de sécurité avancés)

## Configuration du Projet

### Installation

1. Cloner le projet
```bash
git clone [url_du_projet]
cd maison-provence
```

2. Installer les dépendances
```bash
composer install
npm install
```

3. Configuration de l'environnement
```bash
cp .env.local.example .env.local
# Éditer .env.local avec vos configurations
```

4. Configurer la base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Configuration des Secrets en Production

1. Générer les clés de chiffrement :
```bash
php bin/console secrets:generate-keys
```

2. Ajouter les secrets :
```bash
php bin/console secrets:set STRIPE_SECRET_KEY
php bin/console secrets:set DB_PASSWORD
php bin/console secrets:set APP_SECRET
```

### Workers Messenger

Démarrer les workers pour le traitement asynchrone :
```bash
# Worker prioritaire pour les images
php bin/console messenger:consume async_priority -vv

# Worker par défaut pour les emails et autres tâches
php bin/console messenger:consume async_default -vv
```

### Cache

Le projet utilise APCu pour le cache en production. Assurez-vous qu'il est installé :
```bash
pecl install apcu
```

### Performance

- Le cache HTTP est activé en production via HttpCache
- Les requêtes Doctrine sont optimisées avec du cache et des jointures intelligentes
- Le traitement des images est asynchrone via Messenger
- Les emails sont envoyés de manière asynchrone

### Sécurité

- Les configurations sensibles doivent être dans .env.local (non commité)
- Les secrets en production sont gérés via le système de secrets Symfony
- Les headers de sécurité sont configurés via SecurityHeadersSubscriber

## Développement

### Commandes utiles

```bash
# Vider le cache
php bin/console cache:clear

# Vérifier la configuration
php bin/console debug:config

# Lister les routes
php bin/console debug:router

# Vérifier les messages en attente
php bin/console messenger:failed:show
```

### Bonnes pratiques

- Utiliser les DTOs pour les formulaires
- Traiter les tâches lourdes de manière asynchrone
- Optimiser les requêtes Doctrine
- Suivre les standards de code PHP-CS-Fixer

## E-commerce

Le site peut fonctionner en deux modes :
- **Mode E-commerce** : Toutes les fonctionnalités e-commerce sont activées (produits, panier, paiement)
- **Mode Vitrine** : Le site fonctionne comme une vitrine, sans les fonctionnalités e-commerce

### Changer de mode

1. Accéder à l'interface d'administration (/admin)
2. Aller dans "Configuration du site"
3. Dans la section "E-commerce" :
   - Activer/désactiver la e-boutique avec le bouton
   - Personnaliser le message affiché quand la boutique est désactivée
4. Sauvegarder les changements

### Effets du mode vitrine
- Les pages produits ne sont plus accessibles
- Le lien "Produits" est masqué dans la navigation
- Les utilisateurs sont redirigés vers la page d'accueil
- Un message personnalisé est affiché

## Tests

Exécuter les tests unitaires :
```bash
docker-compose exec php bin/phpunit
```

## Architecture

### Entités principales

- **Product**: Gestion des produits
- **Category**: Organisation hiérarchique des catégories
- **SEO**: Configuration SEO par entité
- **Media**: Gestion des médias
- **SiteConfiguration**: Configuration globale du site

### Traits

- **SiteConfigurationTrait**: Accès à la configuration du site
- **SEOTrait**: Fonctionnalités SEO communes

## Sécurité

- Validation stricte des entrées utilisateur
- Protection CSRF
- Filtrage des données SEO
- Gestion sécurisée des uploads de fichiers

## SEO

- Meta tags personnalisables
- Validation des longueurs des meta titles et descriptions
- Gestion des URLs canoniques
- Support des Open Graph tags
- Mots-clés uniques

## Performances

- Optimisation des requêtes Doctrine
- Mise en cache des données
- Gestion efficace des relations entre entités
- Chargement lazy des collections

## Contribution

1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Créer une Pull Request

## Conventions de code

- PSR-12 pour le style de code
- Types stricts PHP
- Documentation PHPDoc
- Messages de commit conventionnels

## Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## Équipe

- [Bensaad Mohammed] - Développeur principal

## Support

Pour toute question ou problème :
- Ouvrir une issue sur GitHub
- Contacter l'équipe de support à support@maison-provence.com
