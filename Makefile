CAKEPHP_SOURCE_DIR ?= ../cakephp
CHRONOS_SOURCE_DIR ?= ../chronos
ELASTIC_SOURCE_DIR ?= ../elastic-search
QUEUE_SOURCE_DIR ?= ../queue
AUTHENTICATION_SOURCE_DIR ?= ../authentication
AUTHORIZATION_SOURCE_DIR ?= ../authorization

BUILD_DIR ?= ./build/api
DEPLOY_DIR ?= ./website

PHP ?= php
COMPOSER := $(CURDIR)/composer.phar
COMPOSER_UPDATE_FLAGS := --no-plugins --no-audit --no-security-blocking --ignore-platform-reqs

.DEFAULT_GOAL := help

CAKEPHP3_RELEASES := \
	3.0|3.0.19|no-vendor \
	3.1|3.1.14|no-vendor \
	3.2|3.2.14|update \
	3.3|3.3.16|update \
	3.4|3.4.14|update \
	3.5|3.5.18|update \
	3.6|3.6.15|update \
	3.7|3.7.9|update \
	3.8|3.8.13|update \
	3.9|3.9.10|update \
	3.10|origin/3.x|update

CAKEPHP4_RELEASES := \
	4.0|4.0.9|update \
	4.1|4.1.7|update \
	4.2|4.2.10|update \
	4.3|4.3.10|update \
	4.4|4.4.18|update \
	4.5|4.5.10|update \
	4.6|origin/4.x|update

CAKEPHP5_RELEASES := \
	5.0|5.0.11|update \
	5.1|5.1.6|update \
	5.2|5.2.11|update \
	5.3|origin/5.x|update

CHRONOS_RELEASES := \
	1.x|origin/1.x|update \
	2.x|origin/2.x|update \
	3.x|origin/3.x|update

ELASTIC_RELEASES := \
	2.x|origin/2.x|update \
	3.x|origin/3.x|update \
	4.x|origin/4.x|update

QUEUE_RELEASES := \
	1.x|origin/1.x|update \
	2.x|origin/2.x|update

AUTHENTICATION_RELEASES := \
	2.x|origin/2.x|update \
	3.x|origin/3.x|update \
	4.x|origin/4.x|update

AUTHORIZATION_RELEASES := \
	2.x|origin/2.x|update \
	3.x|origin/3.x|update

release_version = $(word 1,$(subst |, ,$1))
release_tag = $(word 2,$(subst |, ,$1))
release_mode = $(word 3,$(subst |, ,$1))

CAKEPHP3_VERSIONS := $(foreach release,$(CAKEPHP3_RELEASES),$(call release_version,$(release)))
CAKEPHP4_VERSIONS := $(foreach release,$(CAKEPHP4_RELEASES),$(call release_version,$(release)))
CAKEPHP5_VERSIONS := $(foreach release,$(CAKEPHP5_RELEASES),$(call release_version,$(release)))
CHRONOS_VERSIONS := $(foreach release,$(CHRONOS_RELEASES),$(call release_version,$(release)))
ELASTIC_VERSIONS := $(foreach release,$(ELASTIC_RELEASES),$(call release_version,$(release)))
QUEUE_VERSIONS := $(foreach release,$(QUEUE_RELEASES),$(call release_version,$(release)))
AUTHENTICATION_VERSIONS := $(foreach release,$(AUTHENTICATION_RELEASES),$(call release_version,$(release)))
AUTHORIZATION_VERSIONS := $(foreach release,$(AUTHORIZATION_RELEASES),$(call release_version,$(release)))

.PHONY: help clean deploy install \
	build-cakephp3-all build-cakephp4-all build-cakephp5-all \
	build-chronos-all build-elastic-all build-queue-all \
	build-authentication-all build-authorization-all \
	$(foreach version,$(CAKEPHP3_VERSIONS),build-cakephp-$(version)) \
	$(foreach version,$(CAKEPHP4_VERSIONS),build-cakephp-$(version)) \
	$(foreach version,$(CAKEPHP5_VERSIONS),build-cakephp-$(version)) \
	$(foreach version,$(CHRONOS_VERSIONS),build-chronos-$(version)) \
	$(foreach version,$(ELASTIC_VERSIONS),build-elastic-$(version)) \
	$(foreach version,$(QUEUE_VERSIONS),build-queue-$(version)) \
	$(foreach version,$(AUTHENTICATION_VERSIONS),build-authentication-$(version)) \
	$(foreach version,$(AUTHORIZATION_VERSIONS),build-authorization-$(version))

help:
	@echo "CakePHP API Documentation generator"
	@echo "-----------------------------------"
	@echo ""
	@echo "Tasks:"
	@echo ""
	@echo " clean"
	@echo "   Clean the build and deploy output directories."
	@echo " install"
	@echo "   Install this project's Composer dependencies."
	@echo " deploy"
	@echo "   Move built release directories from $(BUILD_DIR) into $(DEPLOY_DIR)."
	@echo " build-cakephp-<version>"
	@echo "   Versions: $(CAKEPHP3_VERSIONS) $(CAKEPHP4_VERSIONS) $(CAKEPHP5_VERSIONS)"
	@echo " build-chronos-<version>"
	@echo "   Versions: $(CHRONOS_VERSIONS)"
	@echo " build-elastic-<version>"
	@echo "   Versions: $(ELASTIC_VERSIONS)"
	@echo " build-queue-<version>"
	@echo "   Versions: $(QUEUE_VERSIONS)"
	@echo " build-authentication-<version>"
	@echo "   Versions: $(AUTHENTICATION_VERSIONS)"
	@echo " build-authorization-<version>"
	@echo "   Versions: $(AUTHORIZATION_VERSIONS)"
	@echo " build-cakephp3-all build-cakephp4-all build-cakephp5-all"
	@echo " build-chronos-all build-elastic-all build-queue-all"
	@echo " build-authentication-all build-authorization-all"
	@echo ""
	@echo "Variables:"
	@echo ""
	@echo " CAKEPHP_SOURCE_DIR        Location of the CakePHP clone."
	@echo " CHRONOS_SOURCE_DIR       Location of the Chronos clone."
	@echo " ELASTIC_SOURCE_DIR       Location of the elastic-search clone."
	@echo " QUEUE_SOURCE_DIR         Location of the queue clone."
	@echo " AUTHENTICATION_SOURCE_DIR Location of the authentication clone."
	@echo " AUTHORIZATION_SOURCE_DIR  Location of the authorization clone."
	@echo " BUILD_DIR                Output directory. Default: $(BUILD_DIR)"
	@echo " DEPLOY_DIR               Deployment directory. Default: $(DEPLOY_DIR)"
	@echo ""
	@echo "NOTE: Source directories will have their checkout branch changed."
	@echo "      Make sure all working directories are clean."

clean:
	rm -rf $(DEPLOY_DIR)
	rm -rf $(BUILD_DIR)

$(DEPLOY_DIR):
	mkdir -p $(DEPLOY_DIR)

deploy: $(DEPLOY_DIR)
	@test -d "$(BUILD_DIR)" || { echo "Build directory $(BUILD_DIR) does not exist."; exit 1; }
	for release_dir in $(BUILD_DIR)/*; do \
		[ -e "$$release_dir" ] || continue; \
		release="$$(basename "$$release_dir")"; \
		rm -rf "$(DEPLOY_DIR)/$$release"; \
		mv "$$release_dir" "$(DEPLOY_DIR)/"; \
	done

composer.phar:
	curl -sS https://getcomposer.org/installer | php

install: composer.phar
	$(PHP) $(COMPOSER) install

define build_docs
build-$(1)-$(5): install
	cd $($(2)) && git checkout -f $(6)
	$(if $(filter no-vendor,$(7)),cd $($(2)) && rm -rf ./vendor,cd $($(2)) && $(PHP) $(COMPOSER) update $(COMPOSER_UPDATE_FLAGS))
	mkdir -p $(BUILD_DIR)/$(3)/$(5)
	cp -r static/assets/* $(BUILD_DIR)/$(3)/$(5)

	$(PHP) bin/apitool.php generate --config $(4) --version $(5) --tag $(6) \
		--output-dir $(BUILD_DIR)/$(3)/$(5) $($(2))
endef

build-cakephp3-all: $(foreach version,$(CAKEPHP3_VERSIONS),build-cakephp-$(version))
build-cakephp4-all: $(foreach version,$(CAKEPHP4_VERSIONS),build-cakephp-$(version))
build-cakephp5-all: $(foreach version,$(CAKEPHP5_VERSIONS),build-cakephp-$(version))
build-chronos-all: $(foreach version,$(CHRONOS_VERSIONS),build-chronos-$(version))
build-elastic-all: $(foreach version,$(ELASTIC_VERSIONS),build-elastic-$(version))
build-queue-all: $(foreach version,$(QUEUE_VERSIONS),build-queue-$(version))
build-authentication-all: $(foreach version,$(AUTHENTICATION_VERSIONS),build-authentication-$(version))
build-authorization-all: $(foreach version,$(AUTHORIZATION_VERSIONS),build-authorization-$(version))

$(foreach release,$(CAKEPHP3_RELEASES),$(eval $(call build_docs,cakephp,CAKEPHP_SOURCE_DIR,cakephp,cakephp3,$(call release_version,$(release)),$(call release_tag,$(release)),$(call release_mode,$(release)))))
$(foreach release,$(CAKEPHP4_RELEASES),$(eval $(call build_docs,cakephp,CAKEPHP_SOURCE_DIR,cakephp,cakephp4,$(call release_version,$(release)),$(call release_tag,$(release)),$(call release_mode,$(release)))))
$(foreach release,$(CAKEPHP5_RELEASES),$(eval $(call build_docs,cakephp,CAKEPHP_SOURCE_DIR,cakephp,cakephp5,$(call release_version,$(release)),$(call release_tag,$(release)),$(call release_mode,$(release)))))
$(foreach release,$(CHRONOS_RELEASES),$(eval $(call build_docs,chronos,CHRONOS_SOURCE_DIR,chronos,chronos,$(call release_version,$(release)),$(call release_tag,$(release)),$(call release_mode,$(release)))))
$(foreach release,$(ELASTIC_RELEASES),$(eval $(call build_docs,elastic,ELASTIC_SOURCE_DIR,elastic-search,elastic,$(call release_version,$(release)),$(call release_tag,$(release)),$(call release_mode,$(release)))))
$(foreach release,$(QUEUE_RELEASES),$(eval $(call build_docs,queue,QUEUE_SOURCE_DIR,queue,queue,$(call release_version,$(release)),$(call release_tag,$(release)),$(call release_mode,$(release)))))
$(foreach release,$(AUTHENTICATION_RELEASES),$(eval $(call build_docs,authentication,AUTHENTICATION_SOURCE_DIR,authentication,authentication,$(call release_version,$(release)),$(call release_tag,$(release)),$(call release_mode,$(release)))))
$(foreach release,$(AUTHORIZATION_RELEASES),$(eval $(call build_docs,authorization,AUTHORIZATION_SOURCE_DIR,authorization,authorization,$(call release_version,$(release)),$(call release_tag,$(release)),$(call release_mode,$(release)))))
