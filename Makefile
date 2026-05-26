.PHONY: install dev setup help

PHP    ?= php
PORT   ?= 8080

help:
	@echo "Available targets:"
	@echo "  make install   Install PHP dependencies via Composer"
	@echo "  make dev       Start local dev server on http://localhost:$(PORT)"
	@echo "  make setup     install + copy config template (first-time setup)"

install:
	composer install --no-interaction --prefer-dist

dev:
	$(PHP) -S localhost:$(PORT) -t public/ public/index.php

setup: install
	@if [ ! -f app/config.local.php ]; then \
		cp app/config.php app/config.local.php; \
		echo "Created app/config.local.php — edit it with your Stripe/PayPal keys."; \
	else \
		echo "app/config.local.php already exists, skipping."; \
	fi
	@echo ""
	@echo "Setup complete. Run: make dev"
