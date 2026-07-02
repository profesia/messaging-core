.PHONY: help up down init compose container bash php test stan health

# Colors for output
RED := \033[0;31m
YELLOW := \033[0;33m
GREEN := \033[0;32m
NC := \033[0m

# Default PHP version (read from docker/.env, fall back to 8.2)
DEFAULT_PHP_VERSION := $(shell grep '^PHP_VERSION' docker/.env 2>/dev/null | cut -d= -f2)
ifeq ($(DEFAULT_PHP_VERSION),)
	DEFAULT_PHP_VERSION := 8.2
endif

# Use provided PHP_VERSION or default
PHP_VERSION ?= $(DEFAULT_PHP_VERSION)
export PHP_VERSION

# Convert PHP version for project name (replace . and - with _)
UPDATED_PHP_VERSION := $(subst .,_,$(subst -,_,$(PHP_VERSION)))

# Check for NO_TTY environment variable
ifeq ($(NO_TTY),1)
	NO_TTY_FLAG := -T
else
	NO_TTY_FLAG :=
endif

# Docker compose command
COMPOSE := docker compose -p messaging_core_$(UPDATED_PHP_VERSION) -f docker/docker-compose.yml

# Validation function for required files
define validate_env
	@if [ ! -f docker/.env ]; then \
		/bin/echo -e "$(RED)The docker/.env is missing. Please create it from docker/.env.dist$(NC)"; \
		exit 1; \
	fi
endef

help:
	@echo "Usage: make [PHP_VERSION=<version>] <target> [arguments]"
	@echo ""
	@echo "Available targets:"
	@echo "  up               - bring containers up, use DETACH=1 to run in background"
	@echo "  down             - shut containers down"
	@echo "  init             - initial setup for docker containers (build + composer install)"
	@echo "  compose          - wrapper for docker compose (use ARGS='...')"
	@echo "  container        - runs command within messaging_core container (use CMD='...')"
	@echo "  bash             - runs a shell command within messaging_core container (use CMD='...')"
	@echo "  php              - runs a PHP script within messaging_core container (use ARGS='...')"
	@echo "  test             - runs the Pest test suite (use ARGS='...')"
	@echo "  stan             - runs PHPStan static analysis (use ARGS='...')"
	@echo "  health           - runs several commands to check if project healthy"

up:
	$(call validate_env)
	@$(MAKE) down
	@$(COMPOSE) pull
ifeq ($(DETACH),1)
	@$(COMPOSE) up -d $(ARGS)
else
	@$(COMPOSE) up $(ARGS)
endif

down:
	$(call validate_env)
	@$(COMPOSE) down --remove-orphans

init:
	$(call validate_env)
	@$(MAKE) DETACH=1 ARGS="--build" up
	@$(COMPOSE) exec $(NO_TTY_FLAG) messaging_core bash -c "composer install"

compose:
	$(call validate_env)
	@$(COMPOSE) $(ARGS)

container:
	$(call validate_env)
	@$(COMPOSE) exec $(NO_TTY_FLAG) messaging_core $(CMD)

bash:
	$(call validate_env)
	@$(COMPOSE) exec $(NO_TTY_FLAG) messaging_core bash -c "$(CMD)"

php:
	$(call validate_env)
	@$(COMPOSE) exec $(NO_TTY_FLAG) messaging_core bash -c "php $(ARGS)"

test:
	$(call validate_env)
	@$(COMPOSE) exec $(NO_TTY_FLAG) messaging_core bash -c "./vendor/bin/pest $(ARGS)"

stan:
	$(call validate_env)
	@$(COMPOSE) exec $(NO_TTY_FLAG) messaging_core bash -c "./vendor/bin/phpstan analyse $(ARGS)"

health:
	@/bin/echo -e "$(YELLOW)Tests$(NC)"
	@$(MAKE) test
	@echo ""
	@/bin/echo -e "$(YELLOW)Stan$(NC)"
	@$(MAKE) stan ARGS="src"
