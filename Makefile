CAKEPHP_SOURCE_DIR=../cakephp
CHRONOS_SOURCE_DIR=../chronos
ELASTIC_SOURCE_DIR=../elastic-search
QUEUE_SOURCE_DIR=../queue
BUILD_DIR=./build/api
DEPLOY_DIR=./website
PHP_DIR=$(PWD)

.PHONY: clean help
.PHONY: build-all
.PHONY: build-active-and-missing
.ALL: help

# Versions that can be built.
CAKEPHP_VERSIONS = 3.8 3.9 4.0 4.1 4.2

CHRONOS_VERSIONS = 1.x 2.x

ELASTIC_VERSIONS = 2.x 3.x

QUEUE_VERSIONS = 0.x

help:
	@echo "CakePHP API Documentation generator"
	@echo "-----------------------------------"
	@echo ""
	@echo "Tasks:"
	@echo ""
	@echo " clean - Clean the build output directory"
	@echo ""
	@echo " build-x.y - Build the x.y documentation. The versions that can be"
	@echo "             built are:"
	@echo "             $(VERSIONS)"
	@echo " build-all     - Build all versions of the documentation"
	@echo ""
	@echo "Variables:"
	@echo " CAKEPHP-SOURCE_DIR - Define where your cakephp clone is. This clone will have its"
	@echo "                      currently checked out branch manipulated. Default: $(CAKEPHP_SOURCE_DIR)"
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
	php composer.phar install

define cakephp
build-cakephp-$(VERSION): install
	cd $(CAKEPHP_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(CAKEPHP_SOURCE_DIR) && php $(PHP_DIR)/composer.phar update
	mkdir -p $(BUILD_DIR)/cakephp/$(VERSION)
	cp -r static/assets/* $(BUILD_DIR)/cakephp/$(VERSION)

	php bin/apitool.php generate --config cakephp --version $(VERSION) \
		$(CAKEPHP_SOURCE_DIR) $(BUILD_DIR)/cakephp/$(VERSION)
endef

define chronos
build-chronos-$(VERSION): install
	cd $(CHRONOS_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(CHRONOS_SOURCE_DIR) && php $(PHP_DIR)/composer.phar update
	mkdir -p $(BUILD_DIR)/chronos/$(VERSION)
	cp -r static/assets/* $(BUILD_DIR)/chronos/$(VERSION)

	php bin/apitool.php generate --config chronos --version $(VERSION) \
		$(CHRONOS_SOURCE_DIR) $(BUILD_DIR)/chronos/$(VERSION)
endef

define elastic
build-elastic-$(VERSION): install
	cd $(ELASTIC_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(ELASTIC_SOURCE_DIR) && php $(PHP_DIR)/composer.phar update
	mkdir -p $(BUILD_DIR)/elastic-search/$(VERSION)
	cp -r static/assets/* $(BUILD_DIR)/elastic-search/$(VERSION)

	php bin/apitool.php generate --config elastic --version $(VERSION) \
		$(ELASTIC_SOURCE_DIR) $(BUILD_DIR)/elastic-search/$(VERSION)
endef

define queue
build-queue-$(VERSION): install
	cd $(QUEUE_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(QUEUE_SOURCE_DIR) && php $(PHP_DIR)/composer.phar update
	mkdir -p $(BUILD_DIR)/queue/$(VERSION)
	cp -r static/assets/* $(BUILD_DIR)/queue/$(VERSION)

	php bin/apitool.php generate --config queue --version $(VERSION) \
		$(QUEUE_SOURCE_DIR) $(BUILD_DIR)/queue/$(VERSION)
endef

# Build all the versions in a loop.
build-all: $(foreach version, $(CAKEPHP_VERSIONS), build-cakephp-$(version)) $(foreach version, $(CHRONOS_VERSIONS), build-chronos-$(version)) $(foreach version, $(ELASTIC_VERSIONS), build-elastic-$(version)) $(foreach version, $(QUEUE_VERSIONS), build-queue-$(version))

# Generate build targets for cakephp
TAG:=3.8.13
VERSION:=3.8
$(eval $(cakephp))

TAG:=origin/3.x
VERSION:=3.9
$(eval $(cakephp))

TAG:=4.0.9
VERSION:=4.0
$(eval $(cakephp))

TAG:=4.1.7
VERSION:=4.1
$(eval $(cakephp))

TAG:=origin/master
VERSION:=4.2
$(eval $(cakephp))

# Generate build targets for chronos
TAG:=origin/1.x
VERSION:=1.x
$(eval $(chronos))

TAG:=origin/master
VERSION:=2.x
$(eval $(chronos))

# Generate build targets for elastic-search
TAG:=origin/2.x
VERSION:=2.x
$(eval $(elastic))

TAG:=origin/master
VERSION:=3.x
$(eval $(elastic))

# Generate build targets for queue
TAG:=origin/master
VERSION:=0.x
$(eval $(queue))
