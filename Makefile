.PHONY: help install start stop restart build logs shell db-connect tests lint fix-cs analyze qa fixtures test-unit test-functional test-coverage assets-watch assets-build cache-clear migrate restructure

# Couleurs pour le help
HELP_COLOR = \033[36m
NO_COLOR   = \033[0m

help: ## Affiche l'aide
	@echo "$(HELP_COLOR)Usage:$(NO_COLOR)"
	@echo "  make [command]"
	@echo ""
	@echo "$(HELP_COLOR)Commandes disponibles:$(NO_COLOR)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Installe le projet
	docker compose build
	docker compose up -d
	docker compose exec php composer install
	docker compose exec php php bin/console doctrine:database:create --if-not-exists
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec node npm install

start: ## Démarre les conteneurs
	docker compose up -d

stop: ## Arrête les conteneurs
	docker compose down

restart: stop start ## Redémarre les conteneurs

build: ## Reconstruit les conteneurs
	docker compose build
	docker compose up -d

logs: ## Affiche les logs des conteneurs
	docker compose logs -f

shell: ## Ouvre un shell dans le conteneur PHP
	docker compose exec php sh

node-shell: ## Ouvre un shell dans le conteneur Node
	docker compose exec node sh

db-connect: ## Se connecte à la base de données
	docker compose exec database psql -U symfony -d symfony

tests: ## Lance les tests
	docker compose exec php php bin/phpunit

lint: ## Vérifie le style du code
	docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

fix-cs: ## Corrige le style du code
	docker compose exec php vendor/bin/php-cs-fixer fix

analyze: ## Lance l'analyse statique du code
	docker compose exec php vendor/bin/phpstan analyse -l 8 src tests

qa: lint analyze tests ## Lance tous les tests de qualité
	@echo "Tous les tests de qualité sont passés !"

fixtures: ## Charge les fixtures
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction

test-unit: ## Lance les tests unitaires
	docker compose exec php php bin/phpunit --testsuite=Unit

test-functional: ## Lance les tests fonctionnels
	docker compose exec php php bin/phpunit --testsuite=Functional

test-coverage: ## Lance les tests avec couverture de code
	docker compose exec php php bin/phpunit --coverage-html var/coverage

assets-watch: ## Lance la compilation des assets en mode watch
	docker compose exec node npm run dev

assets-build: ## Compile les assets pour la production
	docker compose exec node npm run build

cache-clear: ## Vide le cache
	docker compose exec php php bin/console cache:clear

migrate: ## Lance les migrations
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

restructure: ## Restructure le projet en déplaçant les fichiers de src vers la racine
	@echo "Sauvegarde des fichiers Docker..."
	@mkdir -p temp_backup
	@mv docker docker-compose.yml Makefile README.md temp_backup/ 2>/dev/null || true
	@echo "Déplacement des fichiers de src vers la racine..."
	@mv src/* . 2>/dev/null || true
	@mv src/.* . 2>/dev/null || true
	@echo "Restauration des fichiers Docker..."
	@mv temp_backup/* . 2>/dev/null || true
	@rmdir temp_backup 2>/dev/null || true
	@rm -rf src 2>/dev/null || true
	@echo "Restructuration terminée !"
