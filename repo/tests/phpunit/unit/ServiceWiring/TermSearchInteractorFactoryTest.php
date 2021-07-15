<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\MatchingTermsLookupFactory;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermSearchInteractorFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) )
		);

		$this->mockService(
			'WikibaseRepo.MatchingTermsLookupFactory',
			$this->createMock( MatchingTermsLookupFactory::class )
		);

		$this->mockService(
			'WikibaseRepo.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class )
		);

		$this->mockService(
			'WikibaseRepo.PrefetchingTermLookup',
			$this->createMock( PrefetchingTermLookup::class )
		);

		$this->assertInstanceOf(
			TermSearchInteractorFactory::class,
			$this->getService( 'WikibaseRepo.TermSearchInteractorFactory' )
		);
	}

}
