.PHONY: help install setup-docker setup-sqlite migrate test serve clean

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Install composer dependencies
	composer install

setup-docker: install ## Setup with Docker (MySQL + Redis)
	@echo "🐳 Starting Docker containers..."
	docker-compose up -d
	@echo "⏳ Waiting for MySQL to be ready..."
	@sleep 10
	cp .env.docker .env
	php artisan key:generate
	@echo "✅ Docker setup complete. Run 'make migrate' next."

setup-sqlite: install ## Setup with SQLite (no Docker needed)
	@echo "⚡ Setting up SQLite..."
	cp .env.example .env
	php artisan key:generate
	touch database/database.sqlite
	@echo "✅ SQLite setup complete. Run 'make migrate' next."

migrate: ## Run migrations and seed database
	php artisan migrate --seed

test: ## Run tests
	php artisan test

serve: ## Start development server
	php artisan serve

clean: ## Stop and remove Docker containers
	docker-compose down -v
	rm -f database/database.sqlite

docker-logs: ## View Docker container logs
	docker-compose logs -f

docker-restart: ## Restart Docker containers
	docker-compose restart

quick-docker: setup-docker migrate serve ## Complete Docker setup and start server

quick-sqlite: setup-sqlite migrate serve ## Complete SQLite setup and start server
