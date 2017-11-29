<?php

namespace Wikibase\Lib\Tests\Store;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\TypeDispatchingEntityStore;

/**
 * @covers \Wikibase\Lib\Store\TypeDispatchingEntityStore
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class TypeDispatchingEntityStoreTest extends MediaWikiTestCase {

	// TODO: Test assignFreshId
	// TODO: Test saveEntity
	// TODO: Test saveRedirect
	// TODO: Test deleteEntity
	// TODO: Test userWasLastToEdit
	// TODO: Test updateWatchlist
	// TODO: Test isWatching

	public function testGivenUnknownEntityType_canCreateWithCustomIdForwardsToDefaultService() {
		$id = new PropertyId( 'P1' );
		$store = $this->newInstance( [], 'canCreateWithCustomId', $id );

		$result = $store->canCreateWithCustomId( $id );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenCustomEntityType_canCreateWithCustomIdInstantiatesCustomService() {
		$id = new PropertyId( 'P1' );
		$store = $this->newInstance(
			[
				'property' => function ( EntityStore $defaultService ) use ( $id ) {
					$customService = $this->getMock( EntityStore::class );
					$customService->expects( $this->once() )
						->method( 'canCreateWithCustomId' )
						->with( $id )
						->willReturn( 'fromCustomService' );
					return $customService;
				},
			],
			'canCreateWithCustomId'
		);

		$result = $store->canCreateWithCustomId( $id );
		$this->assertSame( 'fromCustomService', $result );
	}

	/**
	 * @param callable[] $callbacks
	 * @param string $expectedMethod
	 * @param EntityId|null $expectedId
	 *
	 * @return TypeDispatchingEntityStore
	 */
	public function newInstance( array $callbacks, $expectedMethod, EntityId $expectedId = null ) {
		$defaultService = $this->getMock( EntityStore::class );
		$defaultService->expects( $expectedId ? $this->once() : $this->never() )
			->method( $expectedMethod )
			->with( $expectedId )
			->willReturn( 'fromParentService' );

		$lookup = $this->getMock( EntityRevisionLookup::class );
		$lookup->expects( $this->never() )
			->method( 'getEntityRevision' );
		$lookup->expects( $this->never() )
			->method( 'getLatestRevisionId' );

		return new TypeDispatchingEntityStore( $callbacks, $defaultService, $lookup );
	}

}
