# Configuration du Site 🛠️

Ce document détaille les différentes options de configuration disponibles dans Maison Provence.

## Table des matières
- [Configuration générale](#configuration-générale)
- [Mode maintenance](#mode-maintenance)
- [Configuration E-commerce](#configuration-e-commerce)
- [Gestion des médias](#gestion-des-médias)

## Configuration générale

### Nom du site
- Modifie le nom affiché dans le titre des pages
- Apparaît dans le header du site
- Utilisé dans les meta tags SEO

### Description du site
- Description générale utilisée pour le SEO
- Peut contenir du HTML formaté
- Recommandation : 150-160 caractères maximum

## Mode maintenance

### Activation/Désactivation
- Accessible depuis l'interface d'administration
- Affiche une page de maintenance personnalisée
- Redirige tous les visiteurs vers cette page
- Les administrateurs peuvent toujours accéder au site

### Message de maintenance
- Message personnalisable
- Support du HTML pour le formatage
- Affiché sur la page de maintenance

## Configuration E-commerce

### Modes de fonctionnement
Le site peut basculer entre deux modes :

#### Mode E-commerce (par défaut)
- Toutes les fonctionnalités e-commerce sont actives
- Pages produits accessibles
- Système de panier actif
- Processus de paiement disponible

#### Mode Vitrine
- Fonctionnalités e-commerce désactivées
- Pages produits inaccessibles
- Navigation e-commerce masquée
- Message personnalisable pour les visiteurs

### Comment changer de mode
1. Accéder à l'interface d'administration (/admin)
2. Aller dans "Configuration du site"
3. Section "E-commerce" :
   - Utiliser le bouton pour activer/désactiver la e-boutique
   - Personnaliser le message de désactivation
4. Sauvegarder les modifications

### Impacts du changement de mode
- **Navigation** : Le lien "Produits" est automatiquement masqué
- **Redirection** : Les utilisateurs sont redirigés vers la page d'accueil
- **Messages** : Affichage du message personnalisé de désactivation
- **URLs** : Les URLs e-commerce retournent une redirection 302

### Bonnes pratiques
- Personnaliser le message de désactivation
- Informer les utilisateurs à l'avance
- Vérifier les redirections après le changement
- Tester la navigation après la modification

## Gestion des médias

### Configuration des uploads
- Taille maximale des fichiers
- Types de fichiers autorisés
- Répertoires de stockage

### Images des produits
- Dimensions recommandées
- Formats supportés
- Optimisation automatique

## Sécurité

### Accès à la configuration
- Réservé aux administrateurs
- Double authentification recommandée
- Journalisation des modifications

## Sauvegarde

### Configuration automatique
- Sauvegarde quotidienne de la configuration
- Export possible au format JSON
- Historique des modifications
