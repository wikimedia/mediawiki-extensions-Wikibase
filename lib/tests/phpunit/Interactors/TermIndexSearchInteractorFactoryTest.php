<?php

namespace Wikibase\Lib\Tests\Interactors;

use Wikibase\Edrsf\LanguageFallbackChainFactory;
use Wikibase\Edrsf\PrefetchingTermLookup;
use Wikibase\Edrsf\TermIndex;
use Wikibase\Edrsf\TermIndexSearchInteractor;
use Wikibase\Edrsf\TermIndexSearchInteractorFactory;

/**
 * @covers Wikibase\Lib\Interactors\TermIndexSearchInteractorFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 */
class TermIndexSearchInteractorFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testNewInteractorReturnsTermIndexSearchInteractorInstance() {
		$factory = new TermIndexSearchInteractorFactory(
			$this->getMock( TermIndex::class ),
			new LanguageFallbackChainFactory(),
			$this->getMock( PrefetchingTermLookup::class )
		);

		$this->assertInstanceOf( TermIndexSearchInteractor::class, $factory->newInteractor( 'en' ) );
	}

	public function testNewInteractorReturnsFreshInstanceOnMultipleCalls() {
		$factory = new \Wikibase\Edrsf\TermIndexSearchInteractorFactory(
			$this->getMock( TermIndex::class ),
			new LanguageFallbackChainFactory(),
			$this->getMock( PrefetchingTermLookup::class )
		);

		$interactorOne = $factory->newInteractor( 'en' );
		$interactorTwo = $factory->newInteractor( 'en' );

		$this->assertNotSame( $interactorTwo, $interactorOne );
	}

}
