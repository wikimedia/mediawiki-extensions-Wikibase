<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use MediaWikiTestCase;
use User;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Property;
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
					$customService = $this->getMock( EntityStore::class );
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
					return new \stdClass();
				},
			],
			$this->newDefaultService( 'saveEntity' ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( InvalidArgumentException::class );
		$store->saveEntity( Property::newFromType( 'string' ), 'summary', $this->newUser() );
	}

	public function testGivenUnknownEntityType_saveEntityForwardsToDefaultService() {
		$entity = Property::newFromType( 'string' );
		$user = $this->newUser();
		$store = new TypeDispatchingEntityStore(
			[],
			$this->newDefaultService( 'saveEntity', [ $entity, 'summary', $user ] ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->saveEntity( $entity, 'summary', $user );
		$this->assertSame( 'fromDefaultService', $result );
	}

	public function testGivenCustomEntityType_saveEntityInstantiatesCustomService() {
		$entity = Property::newFromType( 'string' );
		$user = $this->newUser();
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $entity, $user ) {
					$customService = $this->getMock( EntityStore::class );
					$customService->expects( $this->once() )
						->method( 'saveEntity' )
						->with( $entity, 'summary', $user )
						->willReturn( 'fromCustomService' );
					return $customService;
				},
			],
			$this->newDefaultService( 'saveEntity' ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->saveEntity( $entity, 'summary', $user );
		$this->assertSame( 'fromCustomService', $result );
	}

	public function testGivenUnknownEntityType_saveRedirectForwardsToDefaultService() {
		$id = new PropertyId( 'P1' );
		$redirect = new EntityRedirect( $id, $id );
		$user = $this->newUser();
		$store = new TypeDispatchingEntityStore(
			[],
			$this->newDefaultService( 'saveRedirect', [ $redirect, 'summary', $user ] ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->saveRedirect( $redirect, 'summary', $user );
		$this->assertSame( 'fromDefaultService', $result );
	}

	public function testGivenCustomEntityType_saveRedirectInstantiatesCustomService() {
		$id = new PropertyId( 'P1' );
		$redirect = new EntityRedirect( $id, $id );
		$user = $this->newUser();
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $redirect, $user ) {
					$customService = $this->getMock( EntityStore::class );
					$customService->expects( $this->once() )
						->method( 'saveRedirect' )
						->with( $redirect, 'summary', $user )
						->willReturn( 'fromCustomService' );
					return $customService;
				},
			],
			$this->newDefaultService( 'saveRedirect' ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->saveRedirect( $redirect, 'summary', $user );
		$this->assertSame( 'fromCustomService', $result );
	}

	public function testGivenUnknownEntityType_deleteEntityForwardsToDefaultService() {
		$id = new PropertyId( 'P1' );
		$user = $this->newUser();
		$store = new TypeDispatchingEntityStore(
			[],
			$this->newDefaultService( 'deleteEntity', [ $id, 'reason', $user ] ),
			$this->newEntityRevisionLookup()
		);

		$store->deleteEntity( $id, 'reason', $user );
	}

	public function testGivenCustomEntityType_deleteEntityInstantiatesCustomService() {
		$id = new PropertyId( 'P1' );
		$user = $this->newUser();
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $user, $id ) {
					$customService = $this->getMock( EntityStore::class );
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
		$id = new PropertyId( 'P1' );
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
		$id = new PropertyId( 'P1' );
		$lastRevId = 23;
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $user, $id ) {
					$customService = $this->getMock( EntityStore::class );
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
		$id = new PropertyId( 'P1' );
		$store = new TypeDispatchingEntityStore(
			[],
			$this->newDefaultService( 'updateWatchlist', [ $user, $id, true ] ),
			$this->newEntityRevisionLookup()
		);

		$store->updateWatchlist( $user, $id, true );
	}

	public function testGivenCustomEntityType_updateWatchlistInstantiatesCustomService() {
		$user = $this->newUser();
		$id = new PropertyId( 'P1' );
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $user, $id ) {
					$customService = $this->getMock( EntityStore::class );
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
		$id = new PropertyId( 'P1' );
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
		$id = new PropertyId( 'P1' );
		$store = new TypeDispatchingEntityStore(
			[
				'property' => function ( EntityStore $defaultService ) use ( $user, $id ) {
					$customService = $this->getMock( EntityStore::class );
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
		$id = new PropertyId( 'P1' );
		$store = new TypeDispatchingEntityStore(
			[],
			$this->newDefaultService( 'canCreateWithCustomId', [ $id ] ),
			$this->newEntityRevisionLookup()
		);

		$result = $store->canCreateWithCustomId( $id );
		$this->assertSame( 'fromDefaultService', $result );
	}

	public function testGivenCustomEntityType_canCreateWithCustomIdInstantiatesCustomService() {
		$id = new PropertyId( 'P1' );
		$store = new TypeDispatchingEntityStore(
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
		$defaultService = $this->getMock( EntityStore::class );

		if ( $expectedArguments ) {
			$mocker = $defaultService->expects( $this->once() )
				->method( $expectedMethod )
				->willReturn( 'fromDefaultService' );
			call_user_func_array( [ $mocker, 'with' ], $expectedArguments );
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
		$lookup = $this->getMock( EntityRevisionLookup::class );
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
		$mock = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
		return $mock;
	}

}
