<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Store\BagOStuffSiteLinkConflictLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityConstraintProviderTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$dbFactory = $this->createMock( RepoDomainDbFactory::class );
		$dbFactory->expects( $this->once() )
			->method( 'newRepoDb' );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory', $dbFactory );
		$this->mockService( 'WikibaseRepo.EntityIdComposer',
			new EntityIdComposer( [] ) );
		$this->mockService( 'WikibaseRepo.BagOStuffSiteLinkConflictLookup',
			$this->createMock( BagOStuffSiteLinkConflictLookup::class ) );
		$this->mockService( 'WikibaseRepo.TermValidatorFactory',
			$this->createMock( TermValidatorFactory::class ) );
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [ 'redirectBadgeItems' => [] ] ) );

		$this->assertInstanceOf(
			EntityConstraintProvider::class,
			$this->getService( 'WikibaseRepo.EntityConstraintProvider' )
		);
	}

}
