<?php

namespace Wikibase\Lib\Tests\Interactors;

use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Interactors\TermIndexSearchInteractor;
use Wikibase\Lib\Interactors\TermIndexSearchInteractorFactory;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\TermIndex;

/**
 * @covers Wikibase\Lib\Interactors\TermIndexSearchInteractorFactory
 *
 * @license GPL-2.0+
 */
class TermIndexSearchInteractorFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testGetInteractorReturnsTheRightInstance() {
		$factory = new TermIndexSearchInteractorFactory(
			$this->getMock( TermIndex::class ),
			WikibaseClient::getDefaultInstance()->getLanguageFallbackChainFactory(),
			$this->getMock( PrefetchingTermLookup::class )
		);

		$this->assertInstanceOf( TermIndexSearchInteractor::class, $factory->getInteractor( 'en' ) );
	}

}
