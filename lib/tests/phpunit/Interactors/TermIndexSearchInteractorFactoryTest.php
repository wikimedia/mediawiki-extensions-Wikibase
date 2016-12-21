<?php

namespace Wikibase\Lib\Tests\Interactors;

use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Interactors\TermIndexSearchInteractor;
use Wikibase\Lib\Interactors\TermIndexSearchInteractorFactory;
use Wikibase\Lib\Interactors\TermSearchInteractor;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\TermIndex;

/**
 * @covers Wikibase\Lib\Interactors\TermIndexSearchInteractorFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 */
class TermIndexSearchInteractorFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testGetInteractorReturnsTermIndexSearchInteractorInstance() {
		$factory = new TermIndexSearchInteractorFactory(
			$this->getMock( TermIndex::class ),
			new LanguageFallbackChainFactory(),
			$this->getMock( PrefetchingTermLookup::class )
		);

		$this->assertInstanceOf( TermSearchInteractor::class, $factory->getInteractor( 'en' ) );
		$this->assertInstanceOf( TermIndexSearchInteractor::class, $factory->getInteractor( 'en' ) );
	}

}
