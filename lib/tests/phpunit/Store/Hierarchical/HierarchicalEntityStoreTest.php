<?php

namespace Wikibase\Lib\Store\Hierarchical;

use LogicException;
use PHPUnit_Framework_TestCase;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;

/**
 * @covers \Wikibase\Lib\Store\Hierarchical\HierarchicalEntityStore
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class HierarchicalEntityStoreTest extends PHPUnit_Framework_TestCase {

	public function provideParentAndChildIds() {
		return [
			[ $this->newParentId() ],
			[ $this->newChildId() ],
		];
	}

	public function testGivenParentId_assignFreshIdForwardsToParentService() {
		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'assignFreshId' );

		$instance = new HierarchicalEntityStore( $parentService, $this->newEntityRevisionLookup() );
		$instance->assignFreshId( $this->newParent() );
	}

	public function testGivenChildId_assignFreshIdFails() {
		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->never() )
			->method( 'assignFreshId' );

		$instance = new HierarchicalEntityStore( $parentService, $this->newEntityRevisionLookup() );
		$this->setExpectedException( LogicException::class );
		$instance->assignFreshId( $this->newChild() );
	}

	public function testGivenParent_saveEntityForwardsToParentService() {
		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'saveEntity' );

		$instance = new HierarchicalEntityStore( $parentService, $this->newEntityRevisionLookup() );
		$instance->saveEntity( $this->newParent(), '', $this->newUser() );
	}

	public function testGivenChildId_saveEntity() {
		$this->markTestIncomplete();
	}

	public function testGivenParentId_saveRedirectForwardsToParentService() {
		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'saveRedirect' );

		$redirect = new EntityRedirect( $this->newParentId(), $this->newParentId() );

		$instance = new HierarchicalEntityStore( $parentService, $this->newEntityRevisionLookup() );
		$instance->saveRedirect( $redirect, '', $this->newUser() );
	}

	public function testGivenChildId_saveRedirectFails() {
		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->never() )
			->method( 'saveRedirect' );

		$redirect = new EntityRedirect( $this->newChildId(), $this->newChildId() );

		$instance = new HierarchicalEntityStore( $parentService, $this->newEntityRevisionLookup() );
		$this->setExpectedException( LogicException::class );
		$instance->saveRedirect( $redirect, '', $this->newUser() );
	}

	public function testGivenParentId_deleteEntityForwardsToParentService() {
		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'deleteEntity' );

		$instance = new HierarchicalEntityStore( $parentService, $this->newEntityRevisionLookup() );
		$instance->deleteEntity( $this->newParentId(), '', $this->newUser() );
	}

	public function testGivenChildId_deleteEntity() {
		$this->markTestIncomplete();
	}

	/**
	 * @dataProvider provideParentAndChildIds
	 */
	public function testGivenAnyId_userWasLastToEditAlwaysForwardsToParentService( $id ) {
		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'userWasLastToEdit' );

		$instance = new HierarchicalEntityStore( $parentService, $this->newEntityRevisionLookup() );
		$instance->userWasLastToEdit( $this->newUser(), $id, 0 );
	}

	/**
	 * @dataProvider provideParentAndChildIds
	 */
	public function testGivenAnyId_updateWatchlistAlwaysForwardsToParentService( $id ) {
		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'updateWatchlist' );

		$instance = new HierarchicalEntityStore( $parentService, $this->newEntityRevisionLookup() );
		$instance->updateWatchlist( $this->newUser(), $id, false );
	}

	/**
	 * @dataProvider provideParentAndChildIds
	 */
	public function testGivenAnyId_isWatchingAlwaysForwardsToParentService( $id ) {
		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'isWatching' );

		$instance = new HierarchicalEntityStore( $parentService, $this->newEntityRevisionLookup() );
		$instance->isWatching( $this->newUser(), $id );
	}

	public function testGivenParentId_canCreateWithCustomIdForwardsToParentService() {
		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'canCreateWithCustomId' );

		$instance = new HierarchicalEntityStore( $parentService, $this->newEntityRevisionLookup() );
		$instance->canCreateWithCustomId( $this->newParentId() );
	}

	public function testGivenChildId_canCreateWithCustomIdFails() {
		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->never() )
			->method( 'canCreateWithCustomId' );

		$instance = new HierarchicalEntityStore( $parentService, $this->newEntityRevisionLookup() );
		$this->setExpectedException( LogicException::class );
		$instance->canCreateWithCustomId( $this->newChildId() );
	}

	/**
	 * @return EntityDocument
	 */
	private function newParent() {
		$mock = $this->getMock( EntityDocument::class );
		$mock->method( 'getId' )
			->willReturn( $this->newParentId() );
		return $mock;
	}

	/**
	 * @return EntityId
	 */
	private function newParentId() {
		$mock = $this->getMockBuilder( EntityId::class )
			->disableOriginalConstructor()
			->getMock();
		return $mock;
	}

	/**
	 * @return EntityDocument
	 */
	private function newChild() {
		$mock = $this->getMock( EntityDocument::class );
		$mock->method( 'getId' )
			->willReturn( $this->newChildId() );
		return $mock;
	}

	/**
	 * @return HierarchicalEntityId
	 */
	private function newChildId() {
		$mock = $this->getMockBuilder( HierarchicalEntityId::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->method( 'getParentId' )
			->willReturn( $this->newParentId() );
		return $mock;
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

	/**
	 * @return EntityRevisionLookup
	 */
	private function newEntityRevisionLookup() {
		$mock = $this->getMock( EntityRevisionLookup::class );
		return $mock;
	}

}
