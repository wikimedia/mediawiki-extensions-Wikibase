<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EditEntityFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.EntityTitleStoreLookup',
			$this->createMock( EntityTitleStoreLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.Store',
			$this->getMockStore()
		);

		$this->mockService(
			'WikibaseRepo.EntityStore',
			$this->createMock( EntityStore::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityPermissionChecker',
			$this->createMock( EntityPermissionChecker::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityDiffer',
			$this->createMock( EntityDiffer::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityPatcher',
			$this->createMock( EntityPatcher::class )
		);

		$this->mockService(
			'WikibaseRepo.EditFilterHookRunner',
			$this->createMock( EditFilterHookRunner::class )
		);

		$this->mockService(
			'WikibaseRepo.Settings',
			new SettingsArray( [
				'maxSerializedEntitySize' => 2048,
			] )
		);

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getStatsdDataFactory' );

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getUserOptionsLookup' );

		$this->mockService( 'WikibaseRepo.LocalEntityTypes',
			[ 'item', 'property' ] );

		$this->assertInstanceOf(
			MediawikiEditEntityFactory::class,
			$this->getService( 'WikibaseRepo.EditEntityFactory' )
		);
	}

	private function getMockStore(): Store {
		$mockStore = $this->createMock( Store::class );

		$mockStore->expects( $this->once() )
			->method( 'getEntityRevisionLookup' )
			->willReturn( $this->createMock( EntityRevisionLookup::class ) );

		return $mockStore;
	}

}
