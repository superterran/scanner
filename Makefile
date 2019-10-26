.PHONY: help

# https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
help: ## This help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

.DEFAULT_GOAL := help
selenium-start-docker: ## launches selenium from a docker container
	docker run -p 4444:4444 --privileged selenium/standalone-chrome:latest
selenium-start-firefox-docker: ## launches selenium from a docker container
	docker run -p 4444:4444 selenium/standalone-firefox
tests: ## runs unit tests
	vendor/bin/phpunit tests
selenium-start-firefox: selenium-download ## start selenium in a mode appropriate for firefox
	java -jar selenium.jar -enablePassThrough false
selenium-start: selenium-download ## start selenium
	java -jar selenium.jar
selenium-download: ## downloads the selenium package
	@if [[ ! -f selenium.jar ]]; then wget --output-document=selenium.jar http://selenium-release.storage.googleapis.com/3.9/selenium-server-standalone-3.9.1.jar; fi;