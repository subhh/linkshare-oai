.PHONY: test
test: phpstan phpunit

phpunit:
	vendor/bin/phpunit

phpstan:
	vendor/bin/phpstan analyse --level 7 src

