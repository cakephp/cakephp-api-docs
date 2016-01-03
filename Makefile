SOURCE_DIR='../cakephp'
CHRONOS_SOURCE_DIR='../chronos'
BUILD_DIR=./build/api
DEPLOY_DIR=./website

.PHONY: clean help
.PHONY: build-all
.PHONY: build-active-and-missing
.ALL: help

# Versions that can be built.
VERSIONS = 1.2 1.3 2.0 2.1 2.2 2.3 2.4 2.5 2.6 2.7 3.0 3.1 3.2

# Versions that are actively developed / maintained.
ACTIVE_VERSIONS = 2.7 3.2


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
	mkdir $(BUILD_DIR)

# Make a macro to save re-typing recipies multiple times
define build3x
build-$(VERSION): $(BUILD_DIR)
	cd $(SOURCE_DIR) && git checkout -f $(TAG)
	# Update the config file, Remove sed crap
	sed -i.bak "s/activeVersion: '.*'/activeVersion: '$(VERSION)'/" templates/cakephp/config.neon
	rm templates/cakephp/config.neon.bak
	[ ! -d $(BUILD_DIR) ] && mkdir $(BUILD_DIR) || true
	# Run Apigen
	php apigen.php --source $(SOURCE_DIR)/src \
		--config ./apigen.neon \
		--exclude $(SOURCE_DIR)/src/Templates \
		--destination $(BUILD_DIR)/$(VERSION) \
		--template-config ./templates/cakephp/config.neon
	# Fix rewrites file to have a opening php tag at the start
	sed -i.bak '1i<?php' $(BUILD_DIR)/$(VERSION)/rewrite.php && rm $(BUILD_DIR)/$(VERSION)/rewrite.php.bak
endef

define build2x
build-$(VERSION):
	cd $(SOURCE_DIR) && git checkout -f $(TAG)
	# Update the config file, Remove sed crap
	sed -i.bak "s/activeVersion: '.*'/activeVersion: '$(VERSION)'/" templates/cakephp/config.neon
	rm templates/cakephp/config.neon.bak
	# Run Apigen
	php apigen.php --source $(SOURCE_DIR)/lib \
		--source $(SOURCE_DIR)/app \
		--config ./apigen.neon \
		--exclude $(SOURCE_DIR)/app/Config \
		--exclude $(SOURCE_DIR)/lib/Cake/Console/Command/AppShell.php \
		--exclude $(SOURCE_DIR)/lib/Cake/Test \
		--exclude $(SOURCE_DIR)/lib/Cake/Console/Templates \
		--destination $(BUILD_DIR)/$(VERSION) \
		--template-config ./templates/cakephp/config.neon
	# Fix rewrites file to have a opening php tag at the start
	sed -i.bak '1i<?php' $(BUILD_DIR)/$(VERSION)/rewrite.php && rm $(BUILD_DIR)/$(VERSION)/rewrite.php.bak
endef

define build1x
build-$(VERSION):
	cd $(SOURCE_DIR) && git checkout -f $(TAG)
	# Update the config file, Remove sed crap
	sed -i.bak "s/activeVersion: '.*'/activeVersion: '$(VERSION)'/" templates/cakephp/config.neon
	rm templates/cakephp/config.neon.bak
	# Run Apigen
	php apigen.php --source $(SOURCE_DIR)/cake/libs \
		--source $(SOURCE_DIR)/cake/console/libs \
		--config ./apigen.neon \
		--exclude $(SOURCE_DIR)/cake/tests \
		--exclude $(SOURCE_DIR)/cake/libs/overloadable_php4.php \
		--exclude $(SOURCE_DIR)/cake/console/templates \
		--destination $(BUILD_DIR)/$(VERSION) \
		--template-config ./templates/cakephp/config.neon
	# Fix rewrites file to have a opening php tag at the start
	sed -i.bak '1i<?php' $(BUILD_DIR)/$(VERSION)/rewrite.php && rm $(BUILD_DIR)/$(VERSION)/rewrite.php.bak
endef

# TODO - Make this more generic so we could use it
# for the elasticsearch plugin as well?
# Perhaps take directories and versions as config parameters?
define chronos
build-chronos-$(VERSION):
	cd $(CHRONOS_SOURCE_DIR) && git checkout -f $(TAG)
	# Update the config file, Remove sed crap
	sed -i.bak "s/activeVersion: '.*'/activeVersion: '$(VERSION)'/" templates/cakephp/config.neon
	rm templates/cakephp/config.neon.bak
	# Run Apigen
	php apigen.php --source $(CHRONOS_SOURCE_DIR) \
		--title 'Chronos' \
		--exclude $(CHRONOS_SOURCE_DIR)/tests \
		--exclude $(CHRONOS_SOURCE_DIR)/vendor \
		--config ./apigen.neon \
		--destination $(BUILD_DIR)/chronos/$(VERSION) \
		--template-config ./templates/cakephp/config.neon
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

TAG:=2.5.8
VERSION:=2.5
$(eval $(build2x))

TAG:=2.6.9
VERSION:=2.6
$(eval $(build2x))

TAG:=origin/2.7
VERSION:=2.7
$(eval $(build2x))

TAG:=3.0.14
VERSION:=3.0
$(eval $(build3x))

TAG:=3.1.6
VERSION:=3.1
$(eval $(build3x))

TAG:=origin/master
VERSION:=3.2
$(eval $(build3x))


# Generate build targets for various 1.x versions
TAG:=origin/1.3
VERSION:=1.3
$(eval $(build1x))

TAG:=1.2.12
VERSION:=1.2
$(eval $(build1x))


# Generate build targets for chronos
TAG:=origin/master
VERSION:=1.0
$(eval $(chronos))
