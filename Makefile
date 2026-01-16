.PHONY: help build up down restart logs ps php-shell node-shell db-shell install sf cc assets watch init setup-tailwind db-migrate db-diff

# Variables
DOCKER_COMPOSE = docker compose
PHP_CONTAINER = mlc_php
NODE_CONTAINER = mlc_node
DB_CONTAINER = mlc_database

# Colors
GREEN = \033[0;32m
YELLOW = \033[1;33m
NC = \033[0m

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-20s$(NC) %s\n", $$1, $$2}'

# Docker commands
build: ## Build les containers Docker
	$(DOCKER_COMPOSE) build

up: ## Démarre les containers
	$(DOCKER_COMPOSE) up -d

down: ## Arrête les containers
	$(DOCKER_COMPOSE) down

restart: down up ## Redémarre les containers

logs: ## Affiche les logs des containers
	$(DOCKER_COMPOSE) logs -f

ps: ## Affiche le status des containers
	$(DOCKER_COMPOSE) ps

# Shell access
php-shell: ## Accède au shell du container PHP
	$(DOCKER_COMPOSE) exec php sh

node-shell: ## Accède au shell du container Node
	$(DOCKER_COMPOSE) exec node sh

db-shell: ## Accède au shell MySQL
	$(DOCKER_COMPOSE) exec database mysql -u $${MYSQL_USER:-mlc_user} -p$${MYSQL_PASSWORD:-mlc_password} $${MYSQL_DATABASE:-mlc_app}

# Symfony commands
install: ## Installe les dépendances (composer + npm)
	$(DOCKER_COMPOSE) exec php composer install
	$(DOCKER_COMPOSE) exec node npm install

sf: ## Execute une commande Symfony (usage: make sf CMD="cache:clear")
	$(DOCKER_COMPOSE) exec php php bin/console $(CMD)

cc: ## Clear le cache Symfony
	$(DOCKER_COMPOSE) exec php php bin/console cache:clear

# Assets commands
assets: ## Build les assets pour la production
	$(DOCKER_COMPOSE) exec node npm run build

watch: ## Watch les assets en développement
	$(DOCKER_COMPOSE) exec node npm run dev

# Init project
init: ## Initialise un nouveau projet Symfony avec Tailwind
	@echo "$(YELLOW)Création du projet Symfony...$(NC)"
	$(DOCKER_COMPOSE) exec php composer create-project symfony/skeleton . --no-interaction
	$(DOCKER_COMPOSE) exec php composer require webapp --no-interaction
	$(DOCKER_COMPOSE) exec php composer require pentatrion/vite-bundle --no-interaction
	@echo "$(YELLOW)Configuration de Tailwind et Vite...$(NC)"
	@cp docker/config/vite.config.js app/vite.config.js
	@cp docker/config/postcss.config.js app/postcss.config.js
	@mkdir -p app/assets/styles
	@cp docker/config/assets/app.js app/assets/app.js
	@cp docker/config/assets/styles/app.css app/assets/styles/app.css
	@echo "$(YELLOW)Installation des dépendances Node...$(NC)"
	$(DOCKER_COMPOSE) exec node npm init -y
	$(DOCKER_COMPOSE) exec node npm pkg set type="module"
	$(DOCKER_COMPOSE) exec node npm pkg set scripts.dev="vite"
	$(DOCKER_COMPOSE) exec node npm pkg set scripts.build="vite build"
	$(DOCKER_COMPOSE) exec node npm install -D tailwindcss @tailwindcss/postcss vite vite-plugin-symfony
	@echo "$(GREEN)Projet Symfony initialisé avec succès!$(NC)"
	@echo "$(GREEN)Accédez à http://localhost:8080$(NC)"

# Database
db-migrate: ## Execute les migrations
	$(DOCKER_COMPOSE) exec php php bin/console doctrine:migrations:migrate --no-interaction

db-diff: ## Génère une migration à partir des entités
	$(DOCKER_COMPOSE) exec php php bin/console doctrine:migrations:diff
