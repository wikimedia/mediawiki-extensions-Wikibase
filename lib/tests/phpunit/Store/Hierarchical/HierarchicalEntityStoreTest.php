<?php

namespace Wikibase\Lib\Store\Hierarchical;

use LogicException;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;

/**
 * @covers \Wikibase\Lib\Store\Hierarchical\HierarchicalEntityStore
 *
 * @group Wikibase
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
		$instance = $this->newInstance( 1, 'assignFreshId' );
		$instance->assignFreshId( $this->newParent() );
	}

	public function testGivenChildId_assignFreshIdFails() {
		$instance = $this->newInstance( 0, 'assignFreshId' );
		$this->setExpectedException( LogicException::class );
		$instance->assignFreshId( $this->newChild() );
	}

	public function testGivenParent_saveEntityForwardsToParentService() {
		$instance = $this->newInstance( 1, 'saveEntity' );
		$result = $instance->saveEntity( $this->newParent(), '', $this->newUser() );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenChildId_saveEntitySavesChildOnParent() {
		$child = $this->newChild();

		$parent = $this->newParent();
		$parent->expects( $this->once() )
			->method( 'setChildEntity' )
			->with( $child );

		$lookup = $this->getMock( EntityRevisionLookup::class );
		$lookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $parent->getId() )
			->willReturn( new EntityRevision( $parent ) );

		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'saveEntity' )
			->with( $parent )
			->willReturn( 'fromParentService' );

		$instance = new HierarchicalEntityStore( $parentService, $lookup );
		$result = $instance->saveEntity( $child, '', $this->newUser() );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenParentId_saveRedirectForwardsToParentService() {
		$redirect = new EntityRedirect( $this->newParentId(), $this->newParentId() );

		$instance = $this->newInstance( 1, 'saveRedirect' );
		$result = $instance->saveRedirect( $redirect, '', $this->newUser() );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenChildId_saveRedirectFails() {
		$redirect = new EntityRedirect( $this->newChildId(), $this->newChildId() );

		$instance = $this->newInstance( 0, 'saveRedirect' );
		$this->setExpectedException( LogicException::class );
		$result = $instance->saveRedirect( $redirect, '', $this->newUser() );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenParentId_deleteEntityForwardsToParentService() {
		$instance = $this->newInstance( 1, 'deleteEntity' );
		$instance->deleteEntity( $this->newParentId(), '', $this->newUser() );
	}

	public function testGivenChildId_deleteEntityRemovesChildFromParent() {
		$childId = $this->newChildId();

		$parent = $this->newParent();
		$parent->expects( $this->once() )
			->method( 'removeChildEntity' )
			->with( $childId );

		$lookup = $this->getMock( EntityRevisionLookup::class );
		$lookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $parent->getId() )
			->willReturn( new EntityRevision( $parent ) );

		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->never() )
			->method( 'deleteEntity' );
		$parentService->expects( $this->once() )
			->method( 'saveEntity' )
			->with( $parent );

		$instance = new HierarchicalEntityStore( $parentService, $lookup );
		$instance->deleteEntity( $childId, '', $this->newUser() );
	}

	/**
	 * @dataProvider provideParentAndChildIds
	 */
	public function testGivenAnyId_userWasLastToEditAlwaysForwardsToParentService( $id ) {
		$instance = $this->newInstance( 1, 'userWasLastToEdit' );
		$result = $instance->userWasLastToEdit( $this->newUser(), $id, 0 );
		$this->assertSame( 'fromParentService', $result );
	}

	/**
	 * @dataProvider provideParentAndChildIds
	 */
	public function testGivenAnyId_updateWatchlistAlwaysForwardsToParentService( $id ) {
		$instance = $this->newInstance( 1, 'updateWatchlist' );
		$instance->updateWatchlist( $this->newUser(), $id, false );
	}

	/**
	 * @dataProvider provideParentAndChildIds
	 */
	public function testGivenAnyId_isWatchingAlwaysForwardsToParentService( $id ) {
		$instance = $this->newInstance( 1, 'isWatching' );
		$result = $instance->isWatching( $this->newUser(), $id );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenParentId_canCreateWithCustomIdForwardsToParentService() {
		$instance = $this->newInstance( 1, 'canCreateWithCustomId' );
		$result = $instance->canCreateWithCustomId( $this->newParentId() );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenChildId_canCreateWithCustomIdFails() {
		$instance = $this->newInstance( 0, 'canCreateWithCustomId' );
		$this->setExpectedException( LogicException::class );
		$instance->canCreateWithCustomId( $this->newChildId() );
	}

	/**
	 * @param string $parentMethod
	 * @param int $expectedCalls
	 *
	 * @return HierarchicalEntityStore
	 */
	private function newInstance( $expectedCalls, $parentMethod ) {
		$lookup = $this->getMock( EntityRevisionLookup::class );
		$lookup->method( 'getEntityRevision' )
			->willReturn( new EntityRevision( $this->newParent() ) );

		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->exactly( $expectedCalls ) )
			->method( $parentMethod )
			->willReturn( 'fromParentService' );

		return new HierarchicalEntityStore( $parentService, $lookup );
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
	 * @return HierarchicalEntityContainer|PHPUnit_Framework_MockObject_MockObject
	 */
	private function newParent() {
		$mock = $this->getMock( HierarchicalEntityContainer::class );
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

}
