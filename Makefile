SOURCE_DIR='../cakephp'
CHRONOS_SOURCE_DIR='../chronos'
BUILD_DIR=./build/api
DEPLOY_DIR=./website

.PHONY: clean help
.PHONY: build-all
.PHONY: build-active-and-missing
.ALL: help

# Versions that can be built.
VERSIONS = 1.2 1.3 2.0 2.1 2.2 2.3 2.4 2.5 2.6 2.7 2.8 2.9 3.0 3.1 3.2 3.3

# Versions that are actively developed / maintained.
ACTIVE_VERSIONS = 2.8 2.9 3.2 3.3


help:
	@echo "CakePHP API Documentation generator"
	@echo "-----------------------------------"
	@echo ""
	@echo "Tasks:"
	@echo " clean - Clean the build output directory"
	@echo ""
	@echo " build-x.y - Build the x.y documentation. The versions that can be"
	@echo "             built are:"
	@echo "             $(VERSIONS)"
	@echo " build-all     - Build all versions of the documentation"
	@echo " build-chronos-1.0 - Build the documentation for chronos."
	@echo " build-active  - Build all the actively developed versions: $(ACTIVE_VERSIONS)"
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


# Make the build output dir
$(BUILD_DIR):
	mkdir -p $(BUILD_DIR)

composer.phar:
	curl -sS https://getcomposer.org/installer | php

install: composer.phar
	php composer.phar install

# Make a macro to save re-typing recipies multiple times
define build3x
build-$(VERSION): $(BUILD_DIR) install
	cd $(SOURCE_DIR) && git checkout -f $(TAG)
	# Update the config file, Remove sed crap
	sed -i.bak "s/activeVersion: '.*'/activeVersion: '$(VERSION)'/" templates/cakephp/config.neon
	rm templates/cakephp/config.neon.bak
	# Run Apigen
	vendor/bin/apigen generate \
		-s $(SOURCE_DIR)/src \
		-d $(BUILD_DIR)/$(VERSION) \
		--title 'CakePHP' \
		--exclude **/Template/**
endef

define build2x
build-$(VERSION): $(BUILD_DIR) install
	cd $(SOURCE_DIR) && git checkout -f $(TAG)
	# Update the config file, Remove sed crap
	sed -i.bak "s/activeVersion: '.*'/activeVersion: '$(VERSION)'/" templates/cakephp/config.neon
	rm templates/cakephp/config.neon.bak
	# Run Apigen
	vendor/bin/apigen generate -s $(SOURCE_DIR)/lib \
		-d $(BUILD_DIR)/$(VERSION) \
		--groups packages \
		--title 'CakePHP' \
		--exclude Config\** \
		--exclude **\Cake\\Console\\Command\\AppShell** \
		--debug \
		--exclude **\Cake\\Test\** \
		--exclude **\Cake\\Console\\Templates\**
endef

define build1x
build-$(VERSION): $(BUILD_DIR) install
	cd $(SOURCE_DIR) && git checkout -f $(TAG)
	# Update the config file, Remove sed crap
	sed -i.bak "s/activeVersion: '.*'/activeVersion: '$(VERSION)'/" templates/cakephp/config.neon
	rm templates/cakephp/config.neon.bak
	# Run Apigen
	vendor/bin/apigen generate -s $(SOURCE_DIR)/cake/libs \
		-s $(SOURCE_DIR)/cake/console/libs \
		-d $(BUILD_DIR)/$(VERSION) \
		--title 'CakePHP' \
		--exclude **overloadable_php4.php
endef

# TODO - Make this more generic so we could use it
# for the elasticsearch plugin as well?
# Perhaps take directories and versions as config parameters?
define chronos
build-chronos-$(VERSION): $(BUILD_DIR) install
	cd $(CHRONOS_SOURCE_DIR) && git checkout -f $(TAG)
	# Update the config file, Remove sed crap
	sed -i.bak "s/activeVersion: '.*'/activeVersion: '$(VERSION)'/" templates/cakephp/config.neon
	sed -i.bak "s/versions: .*/versions: ['$(VERSION)']/" templates/cakephp/config.neon
	rm templates/cakephp/config.neon.bak
	# Run Apigen
	vendor/bin/apigen generate -s $(CHRONOS_SOURCE_DIR) \
		-d $(BUILD_DIR)/chronos/$(VERSION) \
		--title 'Chronos' \
		--exclude **/tests/** \
		--exclude **/vendor/**
endef

# Build all the versions in a loop.
build-all: $(foreach version, $(VERSIONS), build-$(version)) build-chronos-1.0

# Build all active versions in a loop.
build-active: $(foreach version, $(ACTIVE_VERSIONS), build-$(version)) build-chronos-1.0

# Build all active and missing versions in a loop.
build-active-and-missing:
	for version in $(VERSIONS); do \
		IS_ACTIVE=$$(echo $(ACTIVE_VERSIONS) | grep "$$version"); \
		if test -n "$$IS_ACTIVE" || ! test -d "$(BUILD_DIR)/$$version"; then \
			$(MAKE) build-$$version; \
		fi \
	done

# Generate build targets for various 2.x versions.
TAG:=2.0.6
VERSION:=2.0
$(eval $(build2x))

TAG:=2.1.5
VERSION:=2.1
$(eval $(build2x))

TAG:=2.2.9
VERSION:=2.2
$(eval $(build2x))

TAG:=2.3.10
VERSION:=2.3
$(eval $(build2x))

TAG:=2.4.10
VERSION:=2.4
$(eval $(build2x))

TAG:=2.5.9
VERSION:=2.5
$(eval $(build2x))

TAG:=2.6.9
VERSION:=2.6
$(eval $(build2x))

TAG:=2.7.11
VERSION:=2.7
$(eval $(build2x))

TAG:=2.8.9
VERSION:=2.8
$(eval $(build2x))

TAG:=origin/2.x
VERSION:=2.9
$(eval $(build2x))

TAG:=3.0.19
VERSION:=3.0
$(eval $(build3x))

TAG:=3.1.13
VERSION:=3.1
$(eval $(build3x))

TAG:=3.2.14
VERSION:=3.2
$(eval $(build3x))

TAG:=origin/master
VERSION:=3.3
$(eval $(build3x))


# Generate build targets for various 1.x versions
TAG:=1.3.21
VERSION:=1.3
$(eval $(build1x))

TAG:=1.2.12
VERSION:=1.2
$(eval $(build1x))


# Generate build targets for chronos
TAG:=origin/master
VERSION:=1.0
$(eval $(chronos))
