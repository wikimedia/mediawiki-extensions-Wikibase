<?php

namespace Wikibase\Lib\Store\Hierarchical;

use LogicException;
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
		$instance->saveEntity( $this->newParent(), '', $this->newUser() );
	}

	public function testGivenChildId_saveEntitySavesChildOnParent() {
		$instance = $this->newInstance( 1, 'saveEntity' );
		$instance->saveEntity( $this->newChild(), '', $this->newUser() );

		$this->markTestIncomplete();
	}

	public function testGivenParentId_saveRedirectForwardsToParentService() {
		$redirect = new EntityRedirect( $this->newParentId(), $this->newParentId() );

		$instance = $this->newInstance( 1, 'saveRedirect' );
		$instance->saveRedirect( $redirect, '', $this->newUser() );
	}

	public function testGivenChildId_saveRedirectFails() {
		$redirect = new EntityRedirect( $this->newChildId(), $this->newChildId() );

		$instance = $this->newInstance( 0, 'saveRedirect' );
		$this->setExpectedException( LogicException::class );
		$instance->saveRedirect( $redirect, '', $this->newUser() );
	}

	public function testGivenParentId_deleteEntityForwardsToParentService() {
		$instance = $this->newInstance( 1, 'deleteEntity' );
		$instance->deleteEntity( $this->newParentId(), '', $this->newUser() );
	}

	public function testGivenChildId_deleteEntityRemovesChildFromParent() {
		$instance = $this->newInstance( 0, 'deleteEntity' );
		$instance->deleteEntity( $this->newChildId(), '', $this->newUser() );

		$this->markTestIncomplete();
	}

	/**
	 * @dataProvider provideParentAndChildIds
	 */
	public function testGivenAnyId_userWasLastToEditAlwaysForwardsToParentService( $id ) {
		$instance = $this->newInstance( 1, 'userWasLastToEdit' );
		$instance->userWasLastToEdit( $this->newUser(), $id, 0 );
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
		$instance->isWatching( $this->newUser(), $id );
	}

	public function testGivenParentId_canCreateWithCustomIdForwardsToParentService() {
		$instance = $this->newInstance( 1, 'canCreateWithCustomId' );
		$instance->canCreateWithCustomId( $this->newParentId() );
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
		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->exactly( $expectedCalls ) )
			->method( $parentMethod );

		$lookup = $this->getMock( EntityRevisionLookup::class );
		$lookup->method( 'getEntityRevision' )
			->willReturn( new EntityRevision( $this->newParent() ) );

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
	 * @return HierarchicalEntityContainer
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
