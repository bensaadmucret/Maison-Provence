-- Création de la base de données de test
CREATE DATABASE IF NOT EXISTS maison_provence_test;

-- Attribution des droits à l'utilisateur app
GRANT ALL PRIVILEGES ON maison_provence_test.* TO 'app'@'%';
FLUSH PRIVILEGES;
