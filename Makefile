ifneq (,)
  $(error This Makefile requires GNU Make. )
endif

.DEFAULT_GOAL := sami
DOCKER_UID := $(shell id -u)
DOCKER_GID := $(shell id -g)
CURRENT_DIR := $(shell pwd)
PROJECT_NAME := "jms_job_queue_bundle"

.PHONY : sami

sami:
	@docker build --tag=$(PROJECT_NAME)_docs_gen .
	@docker run --rm -u `id -u`:`id -g` -v `pwd`:/build $(PROJECT_NAME)_docs_gen:latest update  .sami.config.php

fix:
	@if docker run --rm \
		-v $(CURRENT_DIR):/data \
		cytopia/php-cs-fixer:latest \
		fix src; then \
		echo "OK"; \
	else \
		echo "Failed! Execute PHP code style fixer to fix this error."; \
		exit 1; \
	fi

fix-diff:
	@ echo "Run PHP codestyle fixer and compare to source..."
	@if docker run --rm \
		-v $(CURRENT_DIR):/data \
		cytopia/php-cs-fixer:latest \
		fix --dry-run --diff src; then \
		echo "OK"; \
	else \
		echo "Failed! Execute PHP code style fixer to fix this error."; \
		exit 1; \
	fi
