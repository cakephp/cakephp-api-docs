CAKEPHP_SOURCE_DIR='../cakephp'
CHRONOS_SOURCE_DIR='../chronos'
BUILD_DIR=./build/api
DEPLOY_DIR=./website
PHP_DIR=$(PWD)

.PHONY: clean help
.PHONY: build-all
.PHONY: build-active-and-missing
.ALL: help

# Versions that can be built.
CAKEPHP_VERSIONS = 4.0 4.1

CHRONOS_VERSIONS = 2.0

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


$(BUILD_DIR):
	mkdir -p $(BUILD_DIR)

composer.phar:
	curl -sS https://getcomposer.org/installer | php

install: composer.phar
	php composer.phar install

define cakephp
build-cakephp-$(VERSION): $(BUILD_DIR) install
	cd $(CAKEPHP_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(CAKEPHP_SOURCE_DIR) && php $(PHP_DIR)/composer.phar update
	cp -r static/* $(BUILD_DIR)

	php bin/apitool.php generate --config config/cakephp.neon --version $(VERSION) \
		--output $(BUILD_DIR)/cakephp/$(VERSION) $(CAKEPHP_SOURCE_DIR)/src
endef

define chronos
build-chronos-$(VERSION): $(BUILD_DIR) install
	cd $(CHRONOS_SOURCE_DIR) && git checkout -f $(TAG)
	cd $(CHRONOS_SOURCE_DIR) && php $(PHP_DIR)/composer.phar update
	cp -r static/* $(BUILD_DIR)

	php bin/apitool.php generate --config config/chronos.neon --version $(VERSION) \
		--output $(BUILD_DIR)/chronos/$(VERSION) $(CHRONOS_SOURCE_DIR)/src
endef

# Build all the versions in a loop.
build-all: $(foreach version, $(CAKEPHP_VERSIONS), build-cakephp-$(version)) $(foreach version, $(CHRONOS_VERSIONS), build-chronos-$(version))

# Generate build targets for cakephp
TAG:=origin/3.x
VERSION:=3.8
$(eval $(cakephp))

TAG:=origin/master
VERSION:=4.0
$(eval $(cakephp))

TAG:=origin/4.next
VERSION:=4.1
$(eval $(cakephp))

# Generate build targets for chronos
TAG:=origin/master
VERSION:=2.0
$(eval $(chronos))
