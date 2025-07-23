phpstan-baseline:
	./vendor/bin/phpstan analyse --generate-baseline=./phpstan-baseline.php --memory-limit=-1 --allow-empty-baseline
