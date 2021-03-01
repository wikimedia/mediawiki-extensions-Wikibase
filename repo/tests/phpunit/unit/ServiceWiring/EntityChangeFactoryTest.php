<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityChangeFactoryTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseRepo.EntityDiffer',
			new EntityDiffer() );
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new DispatchingEntityIdParser( [] ) );
		$this->mockService( 'WikibaseRepo.Logger',
			new NullLogger() );

		$this->assertInstanceOf(
			EntityChangeFactory::class,
			$this->getService( 'WikibaseRepo.EntityChangeFactory' )
		);
	}

}
