<?php

namespace Wikibase\Lib\Tests\Interactors;

use PHPUnit4And6Compat;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Interactors\TermIndexSearchInteractor;
use Wikibase\Lib\Interactors\TermIndexSearchInteractorFactory;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\TermIndex;

/**
 * @covers Wikibase\Lib\Interactors\TermIndexSearchInteractorFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0-or-later
 */
class TermIndexSearchInteractorFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testNewInteractorReturnsTermIndexSearchInteractorInstance() {
		$factory = new TermIndexSearchInteractorFactory(
			$this->getMock( TermIndex::class ),
			new LanguageFallbackChainFactory(),
			$this->getMock( PrefetchingTermLookup::class )
		);

		$this->assertInstanceOf( TermIndexSearchInteractor::class, $factory->newInteractor( 'en' ) );
	}

	public function testNewInteractorReturnsFreshInstanceOnMultipleCalls() {
		$factory = new TermIndexSearchInteractorFactory(
			$this->getMock( TermIndex::class ),
			new LanguageFallbackChainFactory(),
			$this->getMock( PrefetchingTermLookup::class )
		);

		$interactorOne = $factory->newInteractor( 'en' );
		$interactorTwo = $factory->newInteractor( 'en' );

		$this->assertNotSame( $interactorTwo, $interactorOne );
	}

}
