<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\Store\Sql\SqlSiteLinkConflictLookup;
use Wikibase\Repo\Store\Store;
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
		$store = $this->createMock( Store::class );
		$store->expects( $this->once() )
			->method( 'getSiteLinkConflictLookup' )
			->willReturn( $this->createMock( SqlSiteLinkConflictLookup::class ) );
		$this->mockService( 'WikibaseRepo.Store',
			$store );

		$this->assertInstanceOf(
			EntityConstraintProvider::class,
			$this->getService( 'WikibaseRepo.EntityConstraintProvider' )
		);
	}

}
