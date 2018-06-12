<?php
call_user_func(
	function () {
		$classLoader = new \Composer\Autoload\ClassLoader();
		$classLoader->setPsr4( 'Wikibase\DataAccess\\', __DIR__ . '/src' );
		$classLoader->setPsr4( 'Wikibase\DataAccess\Tests\\', __DIR__ . '/tests/phpunit' );
		$classLoader->register();
	}
);
