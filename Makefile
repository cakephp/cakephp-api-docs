CAKEPHP_SOURCE_DIR=../cakephp
CHRONOS_SOURCE_DIR=../chronos
ELASTIC_SOURCE_DIR=../elastic-search
QUEUE_SOURCE_DIR=../queue
AUTHENTICATION_SOURCE_DIR=../authentication
AUTHORIZATION_SOURCE_DIR=../authorization
BUILD_DIR=./build/api
DEPLOY_DIR=./website
PHP=php
PHP_COMPOSER=php
COMPOSER=$(PWD)/composer.phar

.PHONY: clean help
.PHONY: build-cakephp-3
.PHONY: build-cakephp-4
.PHONY: build-cakephp-5
.PHONY: build-chronos-1
.PHONY: build-chronos-2
.PHONY: build-elastic-2
.PHONY: build-elastic-3
.PHONY: build-queue-1
.PHONY: build-authentication-2
.PHONY: build-authorization-2
.PHONY: build-active-and-missing
.ALL: help

# Versions that can be built.
CAKEPHP3_VERSIONS = 3.0 3.1 3.2 3.3 3.4 3.5 3.6 3.7 3.8 3.9 3.10
CAKEPHP4_VERSIONS = 4.0 4.1 4.2 4.3 4.4 4.next
CAKEPHP5_VERSIONS = 5.0

CHRONOS1_VERSIONS = 1.x
CHRONOS2_VERSIONS = 2.x

ELASTIC2_VERSIONS = 2.x
ELASTIC3_VERSIONS = 3.x

QUEUE1_VERSIONS = 0.x

AUTHENTICATION2_VERSIONS = 2.x

AUTHORIZATION2_VERSIONS = 2.x

help:
	@echo "CakePHP API Documentation generator"
	@echo "-----------------------------------"
	@echo ""
	@echo "Tasks:"
	@echo ""
	@echo " clean - Clean the build output directory"
	@echo ""
	@echo " build-name-v - Build the version v documentation. The versions that can be"
	@echo "             built are:"
	@echo "             $(VERSIONS)"
	@echo ""
	@echo "Variables:"
	@echo ""
	@echo " CAKEPHP-SOURCE_DIR - Define where your cakephp clone is. This clone will have its"
	@echo "                      currently checked out branch manipulated. Default: $(CAKEPHP_SOURCE_DIR)"
	@echo " CHRONOS_SOURCE_DIR - Define where your chronos clone is. This clone will have its"
	@echo "                      currently checked out branch manipulated. Default: $(CHRONOS_SOURCE_DIR)"
	@echo " ELASTIC_SOURCE_DIR - Define where your elastic-search clone is. This clone will have its"
	@echo "                      currently checked out branch manipulated. Default: $(ELASTIC_SOURCE_DIR)"
	@echo " QUEUE_SOURCE_DIR   - Define where your queue clone is. This clone will have its"
	@echo "                      currently checked out branch manipulated. Default: $(QUEUE_SOURCE_DIR)"
	@echo " BUILD_DIR  - The directory where the output should go. Default: $(BUILD_DIR)"
	@echo " DEPLOY_DIR - The directory files shold be copied to in `deploy` Default: $(DEPLOY_DIR)"

clean:
	rm -rf $(DEPLOY_DIR)
	rm -rf $(BUILD_DIR)


# Make the deployment directory
$(DEPLOY_DIR):
	mkdir -p $(DEPLOY_DIR)

deploy: $(DEPLOY_DIR)
	for release in $$(ls $(BUILD_DIR)); do \
		rm -rf $(DEPLOY_DIR)/$$release; \
		mkdir -p $(DEPLOY_DIR)/$$release; \
		mv $(BUILD_DIR)/$$release $(DEPLOY_DIR)/; \
	done


composer.phar:
	curl -sS https://getcomposer.org/installer | php

install: composer.phar
	$(PHP) $(COMPOSER) install

define cakephp3-no-vendor
build-cakephp-$(VERSION): install
	cd $(CAKEPHP_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(CAKEPHP_SOURCE_DIR) && rm -rf ./vendor
	mkdir -p $(BUILD_DIR)/cakephp/$(VERSION)
	cp -r static/assets/* $(BUILD_DIR)/cakephp/$(VERSION)

	$(PHP) bin/apitool.php generate --config cakephp3 --version $(VERSION) --tag $(TAG) \
		--output-dir $(BUILD_DIR)/cakephp/$(VERSION) $(CAKEPHP_SOURCE_DIR)
endef

define cakephp3
build-cakephp-$(VERSION): install
	cd $(CAKEPHP_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(CAKEPHP_SOURCE_DIR) && $(PHP_COMPOSER) $(COMPOSER) update --no-plugins
	mkdir -p $(BUILD_DIR)/cakephp/$(VERSION)
	cp -r static/assets/* $(BUILD_DIR)/cakephp/$(VERSION)

	$(PHP) bin/apitool.php generate --config cakephp3 --version $(VERSION) --tag $(TAG) \
		--output-dir $(BUILD_DIR)/cakephp/$(VERSION) $(CAKEPHP_SOURCE_DIR)
endef

define cakephp4
build-cakephp-$(VERSION): install
	cd $(CAKEPHP_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(CAKEPHP_SOURCE_DIR) && $(PHP_COMPOSER) $(COMPOSER) update --no-plugins
	mkdir -p $(BUILD_DIR)/cakephp/$(VERSION)
	cp -r static/assets/* $(BUILD_DIR)/cakephp/$(VERSION)

	$(PHP) bin/apitool.php generate --config cakephp4 --version $(VERSION) --tag $(TAG) \
		--output-dir $(BUILD_DIR)/cakephp/$(VERSION) $(CAKEPHP_SOURCE_DIR)
endef

define cakephp5
build-cakephp-$(VERSION): install
	cd $(CAKEPHP_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(CAKEPHP_SOURCE_DIR) && $(PHP_COMPOSER) $(COMPOSER) update --no-plugins
	mkdir -p $(BUILD_DIR)/cakephp/$(VERSION)
	cp -r static/assets/* $(BUILD_DIR)/cakephp/$(VERSION)

	$(PHP) bin/apitool.php generate --config cakephp4 --version $(VERSION) --tag $(TAG) \
		--output-dir $(BUILD_DIR)/cakephp/$(VERSION) $(CAKEPHP_SOURCE_DIR)
endef

define chronos
build-chronos-$(VERSION): install
	cd $(CHRONOS_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(CHRONOS_SOURCE_DIR) && $(PHP_COMPOSER) $(COMPOSER) update --no-plugins
	mkdir -p $(BUILD_DIR)/chronos/$(VERSION)
	cp -r static/assets/* $(BUILD_DIR)/chronos/$(VERSION)

	$(PHP) bin/apitool.php generate --config chronos --version $(VERSION) --tag $(TAG) \
		--output-dir $(BUILD_DIR)/chronos/$(VERSION) $(CHRONOS_SOURCE_DIR)
endef

define elastic
build-elastic-$(VERSION): install
	cd $(ELASTIC_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(ELASTIC_SOURCE_DIR) && $(PHP_COMPOSER) $(COMPOSER) update --no-plugins
	mkdir -p $(BUILD_DIR)/elastic-search/$(VERSION)
	cp -r static/assets/* $(BUILD_DIR)/elastic-search/$(VERSION)

	$(PHP) bin/apitool.php generate --config elastic --version $(VERSION) --tag $(TAG) \
		--output-dir $(BUILD_DIR)/elastic-search/$(VERSION) $(ELASTIC_SOURCE_DIR)
endef

define queue
build-queue-$(VERSION): install
	cd $(QUEUE_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(QUEUE_SOURCE_DIR) && $(PHP_COMPOSER) $(COMPOSER) update --no-plugins
	mkdir -p $(BUILD_DIR)/queue/$(VERSION)
	cp -r static/assets/* $(BUILD_DIR)/queue/$(VERSION)

	$(PHP) bin/apitool.php generate --config queue --version $(VERSION) --tag $(TAG) \
		--output-dir $(BUILD_DIR)/queue/$(VERSION) $(QUEUE_SOURCE_DIR)
endef

define authentication
build-authentication-$(VERSION): install
	cd $(AUTHENTICATION_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(AUTHENTICATION_SOURCE_DIR) && $(PHP_COMPOSER) $(COMPOSER) update --no-plugins
	mkdir -p $(BUILD_DIR)/authentication/$(VERSION)
	cp -r static/assets/* $(BUILD_DIR)/authentication/$(VERSION)

	$(PHP) bin/apitool.php generate --config authentication --version $(VERSION) --tag $(TAG) \
		--output-dir $(BUILD_DIR)/authentication/$(VERSION) $(AUTHENTICATION_SOURCE_DIR)
endef

define authorization
build-authorization-$(VERSION): install
	cd $(AUTHORIZATION_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(AUTHORIZATION_SOURCE_DIR) && $(PHP_COMPOSER) $(COMPOSER) update --no-plugins
	mkdir -p $(BUILD_DIR)/authorization/$(VERSION)
	cp -r static/assets/* $(BUILD_DIR)/authorization/$(VERSION)

	$(PHP) bin/apitool.php generate --config authorization --version $(VERSION) --tag $(TAG) \
		--output-dir $(BUILD_DIR)/authorization/$(VERSION) $(AUTHORIZATION_SOURCE_DIR)
endef

# Build all the versions in a loop.
build-cakephp3-all: $(foreach version, $(CAKEPHP3_VERSIONS), build-cakephp-$(version))
build-cakephp4-all: $(foreach version, $(CAKEPHP4_VERSIONS), build-cakephp-$(version))
build-cakephp5-all: $(foreach version, $(CAKEPHP5_VERSIONS), build-cakephp-$(version))

build-chronos1-all: $(foreach version, $(CHRONOS1_VERSIONS), build-chronos-$(version))
build-chronos2-all: $(foreach version, $(CHRONOS2_VERSIONS), build-chronos-$(version))

build-elastic2-all: $(foreach version, $(ELASTIC2_VERSIONS), build-elastic-$(version))
build-elastic3-all: $(foreach version, $(ELASTIC3_VERSIONS), build-elastic-$(version))

build-queue1-all: $(foreach version, $(QUEUE1_VERSIONS), build-queue-$(version))

build-elastic2-all: $(foreach version, $(ELASTIC2_VERSIONS), build-elastic-$(version))

build-authentication2-all: $(foreach version, $(AUTHENTICATION2_VERSIONS), build-authentication-$(version))

build-authorization2-all: $(foreach version, $(AUTHORIZATION2_VERSIONS), build-authorization-$(version))

# Generate build targets for cakephp
TAG:=3.0.19
VERSION:=3.0
$(eval $(cakephp3-no-vendor))

TAG:=3.1.14
VERSION:=3.1
$(eval $(cakephp3-no-vendor))

TAG:=3.2.14
VERSION:=3.2
$(eval $(cakephp3))

TAG:=3.3.16
VERSION:=3.3
$(eval $(cakephp3))

TAG:=3.4.14
VERSION:=3.4
$(eval $(cakephp3))

TAG:=3.5.18
VERSION:=3.5
$(eval $(cakephp3))

TAG:=3.6.15
VERSION:=3.6
$(eval $(cakephp3))

TAG:=3.7.9
VERSION:=3.7
$(eval $(cakephp3))

TAG:=3.8.13
VERSION:=3.8
$(eval $(cakephp3))

TAG:=3.9.10
VERSION:=3.9
$(eval $(cakephp3))

TAG:=origin/3.x
VERSION:=3.10
$(eval $(cakephp3))

TAG:=4.0.9
VERSION:=4.0
$(eval $(cakephp4))

TAG:=4.1.7
VERSION:=4.1
$(eval $(cakephp4))

TAG:=4.2.10
VERSION:=4.2
$(eval $(cakephp4))

TAG:=4.3.10
VERSION:=4.3
$(eval $(cakephp4))

TAG:=origin/4.x
VERSION:=4.4
$(eval $(cakephp4))

TAG:=origin/4.next
VERSION:=4.next
$(eval $(cakephp4))

TAG:=origin/5.x
VERSION:=5.0
$(eval $(cakephp5))

# Generate build targets for chronos
TAG:=origin/1.x
VERSION:=1.x
$(eval $(chronos))

TAG:=origin/2.x
VERSION:=2.x
$(eval $(chronos))

# Generate build targets for elastic-search
TAG:=origin/2.x
VERSION:=2.x
$(eval $(elastic))

TAG:=origin/3.x
VERSION:=3.x
$(eval $(elastic))

# Generate build targets for queue
TAG:=origin/master
VERSION:=0.x
$(eval $(queue))

# Generate build targets for authetication
TAG:=origin/2.x
VERSION:=2.x
$(eval $(authentication))

# Generate build targets for authorization
TAG:=origin/2.x
VERSION:=2.x
$(eval $(authorization))
