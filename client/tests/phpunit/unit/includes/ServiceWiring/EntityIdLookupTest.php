<?php

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityIdLookup;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityIdLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.EntityTypeDefinitions',
			$this->createMock( EntityTypeDefinitions::class )
		);
		$this->serviceContainer->expects( $this->once() )
			->method( 'getPageProps' );
		$this->mockService(
			'WikibaseClient.EntityIdParser',
			$this->createMock( EntityIdParser::class )
		);
		$this->assertInstanceOf(
			EntityIdLookup::class,
			$this->getService( 'WikibaseClient.EntityIdLookup' )
		);
	}
}
