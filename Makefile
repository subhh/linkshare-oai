.PHONY: test
test: phpstan phpunit phan

phpunit:
	vendor/bin/phpunit

phpstan:
	vendor/bin/phpstan analyse --level 7 src

phan:
	PHAN_ALLOW_XDEBUG=1 vendor/bin/phan
