<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use MediaWikiCoversValidator;
use User;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\TypeDispatchingEntityStore;

/**
 * @covers \Wikibase\Lib\Store\TypeDispatchingEntityStore
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class TypeDispatchingEntityStoreTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;

	public function testGivenUnknownEntityType_assignFreshIdForwardsToDefaultService() {
		$entity = Property::newFromType( 'string' );
		$store = new TypeDispatchingEntityStore(
			[],
			$this->newDefaultService( 'assignFreshId', [ $entity ] ),
			$this->newEntityRevisionLookup()
		);

		$store->assignFreshId( $entity );
	}

	public function testGivenCustomEntityType_assignFreshIdInstantiatesCustomService() {
		$entity = Property::newFromType( 'string' );
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $entity ) {
					$customService = $this->createMock( EntityStore::class );
					$customService->expects( $this->once() )
						->method( 'assignFreshId' )
						->with( $entity );
					return $customService;
				},
			],
			$this->newDefaultService( 'assignFreshId' ),
			$this->newEntityRevisionLookup()
		);

		$store->assignFreshId( $entity );
	}

	/**
	 * @covers \Wikibase\Lib\Store\TypeDispatchingEntityStore::getStore
	 */
	public function testGivenInvalidCallback_saveEntityFails() {
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) {
					return (object)[];
				},
			],
			$this->newDefaultService( 'saveEntity' ),
			$this->newEntityRevisionLookup()
		);

		$this->expectException( InvalidArgumentException::class );
		$store->saveEntity( Property::newFromType( 'string' ), 'summary', $this->newUser() );
	}

	public function testGivenUnknownEntityType_saveEntityForwardsToDefaultService() {
		$entity = Property::newFromType( 'string' );
		$user = $this->newUser();
		$flags = EDIT_MINOR;
		$baseRevId = 0;
		$tags = [ 'tag' ];
		$store = new TypeDispatchingEntityStore(
			[],
			$this->newDefaultService( 'saveEntity',
				[ $entity, 'summary', $user, $flags, $baseRevId, $tags ] ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->saveEntity( $entity, 'summary', $user, $flags, $baseRevId, $tags );
		$this->assertSame( 'fromDefaultService', $result );
	}

	public function testGivenCustomEntityType_saveEntityInstantiatesCustomService() {
		$entity = Property::newFromType( 'string' );
		$user = $this->newUser();
		$flags = EDIT_MINOR;
		$baseRevId = 0;
		$tags = [ 'tag' ];
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $entity, $user, $flags, $baseRevId, $tags ) {
					$customService = $this->createMock( EntityStore::class );
					$customService->expects( $this->once() )
						->method( 'saveEntity' )
						->with( $entity, 'summary', $user, $flags, $baseRevId, $tags )
						->willReturn( 'fromCustomService' );
					return $customService;
				},
			],
			$this->newDefaultService( 'saveEntity' ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->saveEntity( $entity, 'summary', $user, $flags, $baseRevId, $tags );
		$this->assertSame( 'fromCustomService', $result );
	}

	public function testGivenUnknownEntityType_saveRedirectForwardsToDefaultService() {
		$id = new NumericPropertyId( 'P1' );
		$id2 = new NumericPropertyId( 'P2' );
		$redirect = new EntityRedirect( $id, $id2 );
		$user = $this->newUser();
		$flags = EDIT_MINOR;
		$baseRevId = 0;
		$tags = [ 'tag' ];
		$store = new TypeDispatchingEntityStore(
			[],
			$this->newDefaultService( 'saveRedirect', [ $redirect, 'summary', $user, $flags, $baseRevId, $tags ] ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->saveRedirect( $redirect, 'summary', $user, $flags, $baseRevId, $tags );
		$this->assertSame( 'fromDefaultService', $result );
	}

	public function testGivenCustomEntityType_saveRedirectInstantiatesCustomService() {
		$id = new NumericPropertyId( 'P1' );
		$id2 = new NumericPropertyId( 'P2' );
		$redirect = new EntityRedirect( $id, $id2 );
		$user = $this->newUser();
		$flags = EDIT_MINOR;
		$baseRevId = 0;
		$tags = [ 'tag' ];
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $redirect, $user, $flags, $baseRevId, $tags ) {
					$customService = $this->createMock( EntityStore::class );
					$customService->expects( $this->once() )
						->method( 'saveRedirect' )
						->with( $redirect, 'summary', $user, $flags, $baseRevId, $tags )
						->willReturn( 'fromCustomService' );
					return $customService;
				},
			],
			$this->newDefaultService( 'saveRedirect' ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->saveRedirect( $redirect, 'summary', $user, $flags, $baseRevId, $tags );
		$this->assertSame( 'fromCustomService', $result );
	}

	public function testGivenUnknownEntityType_deleteEntityForwardsToDefaultService() {
		$id = new NumericPropertyId( 'P1' );
		$user = $this->newUser();
		$store = new TypeDispatchingEntityStore(
			[],
			$this->newDefaultService( 'deleteEntity', [ $id, 'reason', $user ] ),
			$this->newEntityRevisionLookup()
		);

		$store->deleteEntity( $id, 'reason', $user );
	}

	public function testGivenCustomEntityType_deleteEntityInstantiatesCustomService() {
		$id = new NumericPropertyId( 'P1' );
		$user = $this->newUser();
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $user, $id ) {
					$customService = $this->createMock( EntityStore::class );
					$customService->expects( $this->once() )
						->method( 'deleteEntity' )
						->with( $id, 'reason', $user );
					return $customService;
				},
			],
			$this->newDefaultService( 'deleteEntity' ),
			$this->newEntityRevisionLookup()
		);

		$store->deleteEntity( $id, 'reason', $user );
	}

	public function testGivenUnknownEntityType_userWasLastToEditForwardsToDefaultService() {
		$user = $this->newUser();
		$id = new NumericPropertyId( 'P1' );
		$lastRevId = 23;
		$store = new TypeDispatchingEntityStore(
			[],
			$this->newDefaultService( 'userWasLastToEdit', [ $user, $id, $lastRevId ] ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->userWasLastToEdit( $user, $id, $lastRevId );
		$this->assertSame( 'fromDefaultService', $result );
	}

	public function testGivenCustomEntityType_userWasLastToEditInstantiatesCustomService() {
		$user = $this->newUser();
		$id = new NumericPropertyId( 'P1' );
		$lastRevId = 23;
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $user, $id ) {
					$customService = $this->createMock( EntityStore::class );
					$customService->expects( $this->once() )
						->method( 'userWasLastToEdit' )
						->with( $user, $id )
						->willReturn( 'fromCustomService' );
					return $customService;
				},
			],
			$this->newDefaultService( 'userWasLastToEdit' ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->userWasLastToEdit( $user, $id, $lastRevId );
		$this->assertSame( 'fromCustomService', $result );
	}

	public function testGivenUnknownEntityType_updateWatchlistForwardsToDefaultService() {
		$user = $this->newUser();
		$id = new NumericPropertyId( 'P1' );
		$store = new TypeDispatchingEntityStore(
			[],
			$this->newDefaultService( 'updateWatchlist', [ $user, $id, true ] ),
			$this->newEntityRevisionLookup()
		);

		$store->updateWatchlist( $user, $id, true );
	}

	public function testGivenCustomEntityType_updateWatchlistInstantiatesCustomService() {
		$user = $this->newUser();
		$id = new NumericPropertyId( 'P1' );
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $user, $id ) {
					$customService = $this->createMock( EntityStore::class );
					$customService->expects( $this->once() )
						->method( 'updateWatchlist' )
						->with( $user, $id );
					return $customService;
				},
			],
			$this->newDefaultService( 'updateWatchlist' ),
			$this->newEntityRevisionLookup()
		);

		$store->updateWatchlist( $user, $id, true );
	}

	public function testGivenUnknownEntityType_isWatchingForwardsToDefaultService() {
		$user = $this->newUser();
		$id = new NumericPropertyId( 'P1' );
		$store = new TypeDispatchingEntityStore(
			[],
			$this->newDefaultService( 'isWatching', [ $user, $id ] ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->isWatching( $user, $id );
		$this->assertSame( 'fromDefaultService', $result );
	}

	public function testGivenCustomEntityType_isWatchingInstantiatesCustomService() {
		$user = $this->newUser();
		$id = new NumericPropertyId( 'P1' );
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $user, $id ) {
					$customService = $this->createMock( EntityStore::class );
					$customService->expects( $this->once() )
						->method( 'isWatching' )
						->with( $user, $id )
						->willReturn( 'fromCustomService' );
					return $customService;
				},
			],
			$this->newDefaultService( 'isWatching' ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->isWatching( $user, $id );
		$this->assertSame( 'fromCustomService', $result );
	}

	public function testGivenUnknownEntityType_canCreateWithCustomIdForwardsToDefaultService() {
		$id = new NumericPropertyId( 'P1' );
		$store = new TypeDispatchingEntityStore(
			[],
			$this->newDefaultService( 'canCreateWithCustomId', [ $id ] ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->canCreateWithCustomId( $id );
		$this->assertSame( 'fromDefaultService', $result );
	}

	public function testGivenCustomEntityType_canCreateWithCustomIdInstantiatesCustomService() {
		$id = new NumericPropertyId( 'P1' );
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $id ) {
					$customService = $this->createMock( EntityStore::class );
					$customService->expects( $this->once() )
						->method( 'canCreateWithCustomId' )
						->with( $id )
						->willReturn( 'fromCustomService' );
					return $customService;
				},
			],
			$this->newDefaultService( 'canCreateWithCustomId' ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->canCreateWithCustomId( $id );
		$this->assertSame( 'fromCustomService', $result );
	}

	/**
	 * @param string $expectedMethod
	 * @param array|null $expectedArguments
	 *
	 * @return TypeDispatchingEntityStore
	 */
	public function newDefaultService( $expectedMethod, array $expectedArguments = null ) {
		$defaultService = $this->createMock( EntityStore::class );

		if ( $expectedArguments ) {
			$defaultService->expects( $this->once() )
				->method( $expectedMethod )
				->with( ...$expectedArguments )
				->willReturn( 'fromDefaultService' );
		} else {
			$defaultService->expects( $this->never() )
				->method( $expectedMethod );
		}

		return $defaultService;
	}

	/**
	 * @return EntityRevisionLookup
	 */
	public function newEntityRevisionLookup() {
		$lookup = $this->createMock( EntityRevisionLookup::class );
		$lookup->expects( $this->never() )
			->method( 'getEntityRevision' );
		$lookup->expects( $this->never() )
			->method( 'getLatestRevisionId' );
		return $lookup;
	}

	/**
	 * @return User
	 */
	private function newUser() {
		return $this->createMock( User::class );
	}

}
