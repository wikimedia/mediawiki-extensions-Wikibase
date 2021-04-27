<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Repo\Validators\EntityConstraintProvider;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityConstraintProviderTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->serviceContainer->expects( $this->once() )
			->method( 'getDBLoadBalancer' );
		$this->mockService( 'WikibaseRepo.EntityIdComposer',
			new EntityIdComposer( [] ) );

		$this->assertInstanceOf(
			EntityConstraintProvider::class,
			$this->getService( 'WikibaseRepo.EntityConstraintProvider' )
		);
	}

}
