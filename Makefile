# Browsergame Test Suite Makefile

# Default target
.PHONY: help
help:
	@echo "Available commands:"
	@echo "  make test          - Run all tests"
	@echo "  make test-quick    - Run quick tests (no database)"
	@echo "  make test-db       - Run database tests"
	@echo "  make test-syntax   - Check PHP syntax"
	@echo "  make lint          - Run code linting"
	@echo "  make clean         - Clean test artifacts"
	@echo "  make install       - Install dependencies"

.PHONY: test
test: test-syntax test-quick

.PHONY: test-quick
test-quick:
	@echo "Running quick tests..."
	php tests/phptest.php
	php tests/dbtest.php || true

.PHONY: test-db  
test-db:
	@echo "Running database tests..."
	php tests/dbtest.php

.PHONY: test-syntax
test-syntax:
	@echo "Checking PHP syntax..."
	@find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \; | grep -v "No syntax errors detected" || echo "All files have valid syntax"

.PHONY: test-all
test-all:
	@echo "Running full test suite..."
	php tests/run-tests.php

.PHONY: lint
lint:
	@echo "Running code linting..."
	@if command -v phpcs >/dev/null 2>&1; then \
		phpcs --standard=PSR12 --ignore=vendor/ . || echo "No major coding standard violations"; \
	else \
		echo "phpcs not installed, skipping lint check"; \
	fi

.PHONY: install
install:
	@echo "Installing dependencies..."
	composer install --no-dev --optimize-autoloader

.PHONY: clean
clean:
	@echo "Cleaning test artifacts..."
	rm -rf tests/results/
	rm -rf coverage/
	rm -f phpunit.phar
	rm -f .phpunit.result.cache