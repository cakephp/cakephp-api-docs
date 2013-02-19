SOURCE_DIR='../cakephp'
BUILD_DIR='../apigen_build'

.PHONY: help
.PHONY: build-all

# Versions that can be built.
# VERSIONS = 1.2 1.3 2.0 2.1 2.2 2.3 2.4
VERSIONS = 2.0 2.1 2.2 2.3 2.4

clean:
	rm -rf $(BUILD_DIR)


# Build all the versions in a loop.
build-all: $(foreach version, $(VERSIONS), build-$(version))

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
		--destination $(BUILD_DIR)/$(VERSION)
endef

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
