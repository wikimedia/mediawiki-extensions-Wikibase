<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityChangeLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.EntityChangeFactory',
			$this->createMock( EntityChangeFactory::class ) );
		$this->mockService( 'WikibaseClient.EntityIdParser',
			new ItemIdParser() );
		$itemAndPropertySource = new DatabaseEntitySource(
			'test',
			'testdb',
			[],
			'',
			'',
			'',
			''
		);
		$this->mockService( 'WikibaseClient.ItemAndPropertySource',
			$itemAndPropertySource );
		$repoDomainDbFactory = $this->createMock( RepoDomainDbFactory::class );
		$repoDomainDbFactory->expects( $this->once() )
			->method( 'newForEntitySource' )
			->with( $itemAndPropertySource );
		$this->mockService( 'WikibaseClient.RepoDomainDbFactory',
			$repoDomainDbFactory );

		$this->assertInstanceOf(
			EntityChangeLookup::class,
			$this->getService( 'WikibaseClient.EntityChangeLookup' )
		);
	}

}
