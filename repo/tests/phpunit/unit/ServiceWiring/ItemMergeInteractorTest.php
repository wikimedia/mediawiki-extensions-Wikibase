<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\Merge\MergeFactory;
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
class ItemMergeInteractorTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockServices( [
			'WikibaseRepo.ChangeOpFactoryProvider'
				=> $this->getMockChangeOpFactoryProvider(),
			'WikibaseRepo.Store'
				=> $this->getMockStore(),
			'WikibaseRepo.EntityStore'
				=> $this->createMock( EntityStore::class ),
			'WikibaseRepo.EntityPermissionChecker'
				=> $this->createMock( EntityPermissionChecker::class ),
			'WikibaseRepo.SummaryFormatter'
				=> $this->createMock( SummaryFormatter::class ),
			'WikibaseRepo.ItemRedirectCreationInteractor'
				=> $this->createMock( ItemRedirectCreationInteractor::class ),
			'WikibaseRepo.EntityTitleStoreLookup'
				=> $this->createMock( EntityTitleStoreLookup::class ),
		] );

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getPermissionManager' );

		$this->assertInstanceOf(
			ItemMergeInteractor::class,
			$this->getService( 'WikibaseRepo.ItemMergeInteractor' )
		);
	}

	private function mockServices( array $serviceNamesToValues ): void {
		foreach ( $serviceNamesToValues as $name => $service ) {
			$this->mockService( $name, $service );
		}
	}

	private function getMockChangeOpFactoryProvider(): ChangeOpFactoryProvider {
		$mockFactoryProvider = $this->createMock( ChangeOpFactoryProvider::class );
		$mockMergeFactory = $this->createMock( MergeFactory::class );

		$mockFactoryProvider->expects( $this->once() )
			->method( 'getMergeFactory' )
			->willReturn( $mockMergeFactory );

		return $mockFactoryProvider;
	}

	private function getMockStore(): Store {
		$mockStore = $this->createMock( Store::class );
		$mockRevisionLookup = $this->createMock( EntityRevisionLookup::class );

		$mockStore->expects( $this->once() )
			->method( 'getEntityRevisionLookup' )
			->willReturn( $mockRevisionLookup );

		return $mockStore;
	}

}
