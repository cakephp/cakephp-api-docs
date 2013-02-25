SOURCE_DIR='../cakephp'
BUILD_DIR='./build/api'

.PHONY: clean
.PHONY: build-all

# Versions that can be built.
VERSIONS = 1.2 1.3 2.0 2.1 2.2 2.3 2.4

clean:
	rm -rf $(BUILD_DIR)

# Make a macro to save re-typing recipies multiple times
define build2x
build-$(VERSION):
	cd $(SOURCE_DIR) && git checkout $(TAG)
	# Update the config file, Remove sed crap
	sed -i.bak "s/activeVersion: '[0-9]\.[0-9]'/activeVersion: '$(VERSION)'/" templates/cakephp/config.neon
	rm templates/cakephp/config.neon.bak
	# Make the build output dir
	[ ! -d $(BUILD_DIR) ] && mkdir $(BUILD_DIR) || true
	# Run Apigen
	php apigen.php --source $(SOURCE_DIR)/lib \
		--config ./apigen.neon \
		--exclude $(SOURCE_DIR)/lib/Cake/Test \
		--exclude $(SOURCE_DIR)/lib/Cake/Console/Templates \
		--skip-doc-path $(SOURCE_DIR)/lib/Cake/Test \
		--skip-doc-path $(SOURCE_DIR)/lib/Cake/Console/Templates \
		--destination $(BUILD_DIR)/$(VERSION) \
		--template-config ./templates/cakephp/config.neon
	# Fix rewirites file to have a opening php tag at the start
	sed -i.bak '1i<?php' $(BUILD_DIR)/$(VERSION)/rewrite.php && rm $(BUILD_DIR)/$(VERSION)/rewrite.php.bak
endef

define build1x
build-$(VERSION):
	cd $(SOURCE_DIR) && git checkout $(TAG)
	# Update the config file, Remove sed crap
	sed -i.bak "s/activeVersion: '[0-9]\.[0-9]'/activeVersion: '$(VERSION)'/" templates/cakephp/config.neon
	rm templates/cakephp/config.neon.bak
	# Make the build output dir
	[ ! -d $(BUILD_DIR) ] && mkdir $(BUILD_DIR) || true
	# Run Apigen
	php apigen.php --source $(SOURCE_DIR)/cake \
		--config ./apigen.neon \
		--exclude $(SOURCE_DIR)/cake/tests \
		--exclude $(SOURCE_DIR)/cake/console/libs/templates \
		--skip-doc-path $(SOURCE_DIR)/cake/tests \
		--destination $(BUILD_DIR)/$(VERSION) \
		--template-config ./templates/cakephp/config.neon
	# Fix rewirites file to have a opening php tag at the start
	sed -i.bak '1i<?php' $(BUILD_DIR)/$(VERSION)/rewrite.php && rm $(BUILD_DIR)/$(VERSION)/rewrite.php.bak
endef

# Build all the versions in a loop.
build-all: $(foreach version, $(VERSIONS), build-$(version))

# Generate build targets for various 2.x versions.
TAG:=2.0.6
VERSION:=2.0
$(eval $(build2x))

TAG:=2.1.4
VERSION:=2.1
$(eval $(build2x))

TAG:=2.2.7
VERSION:=2.2
$(eval $(build2x))

TAG:=2.3.0
VERSION:=2.3
$(eval $(build2x))

TAG:=master
VERSION:=2.4
$(eval $(build2x))

# Generate build targets for various 1.x versions
TAG:=1.3.15
VERSION:=1.3
$(eval $(build1x))

TAG:=1.2.11
VERSION:=1.2
$(eval $(build1x))
