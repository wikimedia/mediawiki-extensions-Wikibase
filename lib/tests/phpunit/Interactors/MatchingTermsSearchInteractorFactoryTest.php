<?php

namespace Wikibase\Lib\Tests\Interactors;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\Lib\Interactors\MatchingTermsLookupSearchInteractor;
use Wikibase\Lib\Interactors\MatchingTermsSearchInteractorFactory;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\MatchingTermsLookup;

/**
 * @covers \Wikibase\Lib\Interactors\MatchingTermsSearchInteractorFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0-or-later
 */
class MatchingTermsSearchInteractorFactoryTest extends \PHPUnit\Framework\TestCase {

	public function testNewInteractorReturnsTermIndexSearchInteractorInstance() {
		$factory = new MatchingTermsSearchInteractorFactory(
			$this->createMock( MatchingTermsLookup::class ),
			new LanguageFallbackChainFactory(),
			$this->createMock( PrefetchingTermLookup::class )
		);

		$this->assertInstanceOf( MatchingTermsLookupSearchInteractor::class, $factory->newInteractor( 'en' ) );
	}

	public function testNewInteractorReturnsFreshInstanceOnMultipleCalls() {
		$factory = new MatchingTermsSearchInteractorFactory(
			$this->createMock( MatchingTermsLookup::class ),
			new LanguageFallbackChainFactory(),
			$this->createMock( PrefetchingTermLookup::class )
		);

		$interactorOne = $factory->newInteractor( 'en' );
		$interactorTwo = $factory->newInteractor( 'en' );

		$this->assertNotSame( $interactorTwo, $interactorOne );
	}

}
