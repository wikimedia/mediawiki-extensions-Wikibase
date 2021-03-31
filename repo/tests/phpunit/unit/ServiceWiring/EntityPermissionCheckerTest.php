<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use HashConfig;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Store\WikiPageEntityStorePermissionChecker;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityPermissionCheckerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntityNamespaceLookup',
			new EntityNamespaceLookup( [] ) );
		$this->mockService( 'WikibaseRepo.EntityTitleLookup',
			$this->createMock( EntityTitleLookup::class ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getPermissionManager' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getMainConfig' )
			->willReturn( new HashConfig( [ 'AvailableRights' => [] ] ) );

		$this->assertInstanceOf(
			WikiPageEntityStorePermissionChecker::class,
			$this->getService( 'WikibaseRepo.EntityPermissionChecker' )
		);
	}

}
