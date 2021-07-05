<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Rdf\EntityStubRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RdfBuilderFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.RdfVocabulary',
			$this->createMock( RdfVocabulary::class ) );
		$this->mockService( 'WikibaseRepo.EntityRdfBuilderFactory',
			$this->createMock( EntityRdfBuilderFactory::class ) );
		$this->mockService( 'WikibaseRepo.EntityContentFactory',
			$this->createMock( EntityContentFactory::class ) );
		$this->mockService( 'WikibaseRepo.EntityStubRdfBuilderFactory',
			$this->createMock( EntityStubRdfBuilderFactory::class ) );
		$this->mockService( 'WikibaseRepo.EntityRevisionLookup',
			$this->createMock( EntityRevisionLookup::class ) );

		$this->assertInstanceOf(
			RdfBuilderFactory::class,
			$this->getService( 'WikibaseRepo.RdfBuilderFactory' )
		);
	}

}
