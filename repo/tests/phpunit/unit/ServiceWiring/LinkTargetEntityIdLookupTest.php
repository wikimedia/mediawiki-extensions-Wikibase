<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LinkTargetEntityIdLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.EntityNamespaceLookup',
			$this->createMock( EntityNamespaceLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityIdParser',
			$this->createMock( EntityIdParser::class )
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
			LinkTargetEntityIdLookup::class,
			$this->getService( 'WikibaseRepo.LinkTargetEntityIdLookup' )
		);
	}

}
