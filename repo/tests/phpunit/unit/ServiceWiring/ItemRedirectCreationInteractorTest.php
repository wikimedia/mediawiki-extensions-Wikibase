<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemRedirectCreationInteractorTest extends ServiceWiringTestCase {

	public function testConstruction(): void {

		$this->mockService(
			'WikibaseRepo.Store',
			$this->getMockStore( [
				'getEntityRevisionLookup' => $this->createMock( EntityRevisionLookup::class ),
				'getEntityRedirectLookup' => $this->createMock( EntityRedirectLookup::class ),
			] )
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
			'WikibaseRepo.SummaryFormatter',
			$this->createMock( SummaryFormatter::class )
		);

		$this->mockService(
			'WikibaseRepo.EditFilterHookRunner',
			$this->createMock( EditFilterHookRunner::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityTitleStoreLookup',
			$this->createMock( EntityTitleStoreLookup::class )
		);

		$this->assertInstanceOf(
			ItemRedirectCreationInteractor::class,
			$this->getService( 'WikibaseRepo.ItemRedirectCreationInteractor' )
		);
	}

	private function getMockStore( array $methodsToServices ): Store {
		$mockStore = $this->createMock( Store::class );

		foreach ( $methodsToServices as $method => $service ) {
			$mockStore->expects( $this->once() )
				->method( $method )
				->willReturn( $service );
		}

		return $mockStore;
	}

}
