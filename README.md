# Maison Provence 🏡

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
