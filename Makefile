SOURCE_DIR='../cakephp'
BUILD_DIR='../apigen_build'

.PHONY: help
.PHONY: build-all

# Versions that can be built.
VERSIONS = 1.2 1.3 2.0 2.1 2.2 2.3 2.4

clean:
	rm -rf $(BUILD_DIR)


# Build all the versions in a loop.
build-all: $(foreach version, $(VERSIONS), build-$(version))

build-2.0:
	# Checkout the right version of CakePHP
	cd $(SOURCE_DIR) && git checkout 2.0.6
	# Update the config file, Remove sed crap
	sed -i.bak -E -e "s#(\s*activeVersion\:)[ ]*\'[0-9]\.[0-9]\'#\1 '2.1'#" templates/cakephp/config.neon
	rm templates/cakephp/config.neon.bak
	# Make the build output dir
	[ ! -d $(BUILD_DIR) ] && mkdir $(BUILD_DIR) || true
	# Run Apigen
	php apigen.php --source $(SOURCE_DIR)/lib \
		--exclude $(SOURCE_DIR)/lib/Cake/Test \
		--skip-doc-path $(SOURCE_DIR)/lib/Cake/Test \
		--destination $(BUILD_DIR)/2.0
