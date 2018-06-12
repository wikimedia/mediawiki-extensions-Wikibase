<?php

use Wikibase\ClientHooks;
use Wikibase\PopulateEntityUsage;
use Wikibase\PopulateInterwiki;
use Wikibase\Test\MockClientStore;
use Wikibase\UpdateSubscriptions;

call_user_func(
	function () {
		$classLoader = new \Composer\Autoload\ClassLoader();
		$classLoader->setPsr4( 'Wikibase\Client\\', __DIR__ . '/includes' );
		$classLoader->setPsr4( 'Wikibase\Client\Tests\\', __DIR__ . '/tests/phpunit/includes' );

		$classLoader->addClassMap(
			[
				MockClientStore::class => __DIR__ . '/tests/phpunit/MockClientStore.php',
				ClientHooks::class => __DIR__ . '/ClientHooks.php',
				PopulateEntityUsage::class => __DIR__ . '/maintenance/populateEntityUsage.php',
				PopulateInterwiki::class => __DIR__ . '/maintenance/populateInterwiki.php',
				UpdateSubscriptions::class => __DIR__ . '/maintenance/updateSubscriptions.php',
			]
		);

		$classLoader->register();
	}
);
