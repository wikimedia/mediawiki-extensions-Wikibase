<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\PrefetchingTermLookupFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\MatchingTermsLookupFactory;
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
			new EntitySourceDefinitions( [], new EntityTypeDefinitions( [] ) )
		);

		$this->mockService(
			'WikibaseRepo.PrefetchingTermLookupFactory',
			$this->createMock( PrefetchingTermLookupFactory::class )
		);

		$this->mockService(
			'WikibaseRepo.MatchingTermsLookupFactory',
			$this->createMock( MatchingTermsLookupFactory::class )
		);

		$this->mockService(
			'WikibaseRepo.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class )
		);

		$this->assertInstanceOf(
			TermSearchInteractorFactory::class,
			$this->getService( 'WikibaseRepo.TermSearchInteractorFactory' )
		);
	}

}
