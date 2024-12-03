# Maison Provence 🏡

[![PHP Version](https://img.shields.io/badge/PHP-8.2-777BB4.svg?style=flat-square&logo=php)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/Symfony-7.1-000000.svg?style=flat-square&logo=symfony)](https://symfony.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-13-336791.svg?style=flat-square&logo=postgresql)](https://www.postgresql.org)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)
[![GitHub Actions](https://img.shields.io/github/actions/workflow/status/bensaadmucret/Maison-Provence/ci.yml?branch=main&style=flat-square)](https://github.com/bensaadmucret/Maison-Provence/actions)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=bensaadmucret_Maison-Provence&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=bensaadmucret_Maison-Provence)
[![Coverage Status](https://coveralls.io/repos/github/bensaadmucret/Maison-Provence/badge.svg?branch=main)](https://coveralls.io/github/bensaadmucret/Maison-Provence?branch=main)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat-square)](https://phpstan.org/)

Une plateforme e-commerce moderne développée avec Symfony 7.1, mettant l'accent sur l'optimisation SEO et la gestion efficace des produits.

## 🚀 Fonctionnalités

- Gestion complète des produits et catégories
- Optimisation SEO avancée pour chaque entité
- Gestion des médias et images
- Configuration du site centralisée
- Interface d'administration intuitive
- Validation robuste des données
- Tests unitaires complets

## 🛠️ Technologies

- **Framework**: Symfony 7.1
- **Base de données**: PostgreSQL
- **ORM**: Doctrine
- **Tests**: PHPUnit
- **Templates**: Twig
- **Docker**: Environnement de développement conteneurisé

## 📋 Prérequis

- Docker et Docker Compose
- PHP 8.2 ou supérieur
- Composer
- Node.js et Yarn (pour les assets)

## 🔧 Installation

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

## 🚀 CI/CD

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

## 🛍️ Configuration E-commerce

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

## 🧪 Tests

Exécuter les tests unitaires :
```bash
docker-compose exec php bin/phpunit
```

## 🏗️ Architecture

### Entités principales

- **Product**: Gestion des produits
- **Category**: Organisation hiérarchique des catégories
- **SEO**: Configuration SEO par entité
- **Media**: Gestion des médias
- **SiteConfiguration**: Configuration globale du site

### Traits

- **SiteConfigurationTrait**: Accès à la configuration du site
- **SEOTrait**: Fonctionnalités SEO communes

## 🔐 Sécurité

- Validation stricte des entrées utilisateur
- Protection CSRF
- Filtrage des données SEO
- Gestion sécurisée des uploads de fichiers

## 🌐 SEO

- Meta tags personnalisables
- Validation des longueurs des meta titles et descriptions
- Gestion des URLs canoniques
- Support des Open Graph tags
- Mots-clés uniques

## 📈 Performances

- Optimisation des requêtes Doctrine
- Mise en cache des données
- Gestion efficace des relations entre entités
- Chargement lazy des collections

## 🤝 Contribution

1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Créer une Pull Request

## 📝 Conventions de code

- PSR-12 pour le style de code
- Types stricts PHP
- Documentation PHPDoc
- Messages de commit conventionnels

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👥 Équipe

- [Bensaad Mohammed] - Développeur principal


## 📞 Support

Pour toute question ou problème :
- Ouvrir une issue sur GitHub
- Contacter l'équipe de support à support@maison-provence.com
