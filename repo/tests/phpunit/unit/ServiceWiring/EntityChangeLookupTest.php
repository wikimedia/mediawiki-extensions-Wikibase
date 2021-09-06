<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityChangeLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntityChangeFactory',
			$this->createMock( EntityChangeFactory::class ) );
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new ItemIdParser() );
		$repoDomainDbFactory = $this->createMock( RepoDomainDbFactory::class );
		$repoDomainDbFactory->expects( $this->once() )
			->method( 'newRepoDb' );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory',
			$repoDomainDbFactory );

		$this->assertInstanceOf(
			EntityChangeLookup::class,
			$this->getService( 'WikibaseRepo.EntityChangeLookup' )
		);
	}

}
