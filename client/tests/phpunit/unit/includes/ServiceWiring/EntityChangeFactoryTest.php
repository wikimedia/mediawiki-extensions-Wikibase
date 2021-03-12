<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\Lib\Changes\EntityChangeFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityChangeFactoryTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseClient.EntityDiffer',
			new EntityDiffer() );
		$this->mockService( 'WikibaseClient.EntityIdParser',
			new DispatchingEntityIdParser( [] ) );
		$this->mockService( 'WikibaseClient.Logger',
			new NullLogger() );

		$this->assertInstanceOf(
			EntityChangeFactory::class,
			$this->getService( 'WikibaseClient.EntityChangeFactory' )
		);
	}

}
