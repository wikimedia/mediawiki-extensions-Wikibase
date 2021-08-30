<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityTitleStoreLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			$this->createMock( EntityTypeDefinitions::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityContentFactory',
			$this->createMock( EntityContentFactory::class )
		);

		$this->mockService(
			'WikibaseRepo.EntitySourceDefinitions',
			$this->createMock( EntitySourceDefinitions::class )
		);

		$this->mockService(
			'WikibaseRepo.LocalEntitySource',
			$this->createMock( DatabaseEntitySource::class )
		);

		$this->assertInstanceOf(
			EntityTitleStoreLookup::class,
			$this->getService( 'WikibaseRepo.EntityTitleStoreLookup' )
		);
	}

}
