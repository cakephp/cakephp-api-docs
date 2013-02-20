SOURCE_DIR='../cakephp'
BUILD_DIR='../apigen_build'

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
	sed -i.bak -E -e "s#(\s*activeVersion\:)[ ]*\'[0-9]\.[0-9]\'#\1 '$(VERSION)'#" templates/cakephp/config.neon
	rm templates/cakephp/config.neon.bak
	# Make the build output dir
	[ ! -d $(BUILD_DIR) ] && mkdir $(BUILD_DIR) || true
	# Run Apigen
	php apigen.php --source $(SOURCE_DIR)/lib \
		--exclude $(SOURCE_DIR)/lib/Cake/Test \
		--skip-doc-path $(SOURCE_DIR)/lib/Cake/Test \
		--destination $(BUILD_DIR)/$(VERSION) \
		--template-config ./templates/cakephp/config.neon
endef

define build1x
build-$(VERSION):
	cd $(SOURCE_DIR) && git checkout $(TAG)
	# Update the config file, Remove sed crap
	sed -i.bak -E -e "s#(\s*activeVersion\:)[ ]*\'[0-9]\.[0-9]\'#\1 '$(VERSION)'#" templates/cakephp/config.neon
	rm templates/cakephp/config.neon.bak
	# Make the build output dir
	[ ! -d $(BUILD_DIR) ] && mkdir $(BUILD_DIR) || true
	# Run Apigen
	php apigen.php --source $(SOURCE_DIR)/cake \
		--exclude $(SOURCE_DIR)/cake/tests \
		--skip-doc-path $(SOURCE_DIR)/cake/tests \
		--destination $(BUILD_DIR)/$(VERSION) \
		--template-config ./templates/cakephp/config.neon
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
