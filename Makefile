SOURCE_DIR='../cakephp'
CHRONOS_SOURCE_DIR='../chronos'
BUILD_DIR=./build/api
DEPLOY_DIR=./website
PHP_DIR=$(PWD)

.PHONY: clean help
.PHONY: build-all
.PHONY: build-active-and-missing
.ALL: help

# Versions that can be built.
VERSIONS = 4.0 4.1

CHRONOS_VERSIONS =

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
	@echo " SOURCE_DIR - Define where your cakephp clone is. This clone will have its"
	@echo "              currently checked out branch manipulated. Default: $(SOURCE_DIR)"
	@echo " BUILD_DIR  - The directory where the output should go. Default: $(BUILD_DIR)"
	@echo " DEPLOY_DIR - The directory files shold be copied to in `deploy` Default: $(DEPLOY_DIR)"

clean:
	rm -rf $(BUILD_DIR)


# Make the deployment directory
$(DEPLOY_DIR):
	mkdir $(DEPLOY_DIR)

deploy: $(DEPLOY_DIR)
	for release in $$(ls $(BUILD_DIR)); do \
		rm -rf $(DEPLOY_DIR)/$$release; \
		mv $(BUILD_DIR)/$$release $(DEPLOY_DIR)/; \
	done


composer.phar:
	curl -sS https://getcomposer.org/installer | php

install: composer.phar
	php composer.phar install

static-%:
	mkdir -p $(BUILD_DIR)/$*
	cp -r static/* $(BUILD_DIR)/$*

define build4x
build-$(VERSION): install static-$(VERSION)
	cd $(SOURCE_DIR) && git checkout -f $(TAG)
	cd $(SOURCE_DIR) && php $(PHP_DIR)/composer.phar update

	php bin/apitool.php generate --config config/cakephp.neon --version $(VERSION) \
		--output $(BUILD_DIR)/$(VERSION) $(SOURCE_DIR)/src
endef

# Build all the versions in a loop.
build-all: $(foreach version, $(VERSIONS), build-$(version))

# Generate build targets for 4.x
TAG:=origin/master
VERSION:=4.0
$(eval $(build4x))

TAG:=origin/4.next
VERSION:=4.1
$(eval $(build4x))
