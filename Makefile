# Makefile for WPRuby Delivery Estimates for WooCommerce (Lite).

PLUGIN_SLUG ?= wpruby-delivery-estimates
MAIN_FILE ?= estimated-delivery-and-dispatch-dates-for-woocommerce.php
VERSION ?= $(shell php -r '$$file = file_get_contents("$(MAIN_FILE)"); preg_match("/^\s*\*\s*Version:\s*(.+)$$/mi", $$file, $$m); echo isset($$m[1]) ? trim($$m[1]) : "0.0.0";')
DIST_DIR ?= dist
BUILD_DIR ?= build
PACKAGE_DIR := $(BUILD_DIR)/$(PLUGIN_SLUG)
ZIP_FILE := $(DIST_DIR)/$(PLUGIN_SLUG).zip

.PHONY: install test test-unit test-integration lint assets build zip clean ci validate-build

install:
	composer install
	npm ci

test:
	composer test

test-unit:
	composer test:unit

test-integration:
	composer test:integration

lint:
	composer lint

assets:
	npm ci
	npm run build

clean:
	rm -rf "$(BUILD_DIR)"
	rm -f "$(DIST_DIR)/$(PLUGIN_SLUG).zip"

build: clean assets zip validate-build

zip:
	mkdir -p "$(PACKAGE_DIR)" "$(DIST_DIR)"
	cp -p "$(MAIN_FILE)" readme.txt uninstall.php "$(PACKAGE_DIR)/"
	cp -p package.json vite.config.js "$(PACKAGE_DIR)/"
	rsync -a includes "$(PACKAGE_DIR)/"
	rsync -a templates "$(PACKAGE_DIR)/"
	rsync -a languages "$(PACKAGE_DIR)/"
	mkdir -p "$(PACKAGE_DIR)/assets/admin/dist" "$(PACKAGE_DIR)/assets/admin/vue" "$(PACKAGE_DIR)/assets/frontend"
	rsync -a assets/admin/dist/ "$(PACKAGE_DIR)/assets/admin/dist/"
	rsync -a assets/admin/vue/ "$(PACKAGE_DIR)/assets/admin/vue/"
	rsync -a assets/frontend/ "$(PACKAGE_DIR)/assets/frontend/"
	test -f "$(PACKAGE_DIR)/$(MAIN_FILE)"
	test -f "$(PACKAGE_DIR)/assets/admin/dist/app.js"
	test -f "$(PACKAGE_DIR)/assets/admin/dist/app.css"
	test -d "$(PACKAGE_DIR)/assets/admin/vue"
	test -f "$(PACKAGE_DIR)/package.json"
	test -f "$(PACKAGE_DIR)/vite.config.js"
	cd "$(BUILD_DIR)" && zip -qr "../$(ZIP_FILE)" "$(PLUGIN_SLUG)"

validate-build:
	test -s "$(ZIP_FILE)"
	unzip -l "$(ZIP_FILE)" "$(PLUGIN_SLUG)/$(MAIN_FILE)" >/dev/null
	unzip -l "$(ZIP_FILE)" "$(PLUGIN_SLUG)/assets/admin/dist/app.js" >/dev/null
	unzip -l "$(ZIP_FILE)" "$(PLUGIN_SLUG)/assets/admin/dist/app.css" >/dev/null
	unzip -l "$(ZIP_FILE)" "$(PLUGIN_SLUG)/assets/admin/vue/main.js" >/dev/null
	unzip -l "$(ZIP_FILE)" "$(PLUGIN_SLUG)/package.json" >/dev/null
	unzip -l "$(ZIP_FILE)" "$(PLUGIN_SLUG)/vite.config.js" >/dev/null
	! unzip -l "$(ZIP_FILE)" | rg -q '$(PLUGIN_SLUG)/(tests|node_modules|\.git|\.github|\.idea|vendor|build|dist)/'
	! unzip -l "$(ZIP_FILE)" | rg -q '$(PLUGIN_SLUG)/(composer\.(json|lock)|package-lock\.json|phpunit\.xml(\.dist)?|Makefile|\.env)'
	! unzip -l "$(ZIP_FILE)" | rg -q '$(PLUGIN_SLUG)/includes/Licensing/'
	! unzip -l "$(ZIP_FILE)" | rg -q '$(PLUGIN_SLUG)/includes/Domain/Rule\.php'
	! unzip -l "$(ZIP_FILE)" | rg -q '$(PLUGIN_SLUG)/assets/frontend/countdown\.js'
	@echo "Built $(ZIP_FILE)"

ci:
	composer ci
	npm ci
	npm run build
