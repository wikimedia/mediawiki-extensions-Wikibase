<?php

namespace Wikibase\Lib\Store\Hierarchical;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @covers \Wikibase\Lib\Store\Hierarchical\HierarchicalEntityRevisionLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class HierarchicalEntityRevisionLookupTest extends PHPUnit_Framework_TestCase {

	public function testGivenParentId_getEntityRevisionForwardsToParentService() {
		$parentId = $this->newParentId();
		$instance = $this->newInstance( 'getEntityRevision', $parentId );
		$instance->getEntityRevision( $parentId );
	}

	public function testGivenChildId_getEntityRevisionForwardsParentIdToParentService() {
		$childId = $this->newChildId();
		$instance = $this->newInstance( 'getEntityRevision', $childId->getParentId() );
		$instance->getEntityRevision( $childId );
	}

	public function testGivenParentId_getLatestRevisionIdForwardsToParentService() {
		$parentId = $this->newParentId();
		$instance = $this->newInstance( 'getLatestRevisionId', $parentId );
		$instance->getLatestRevisionId( $parentId );
	}

	public function testGivenChildId_getLatestRevisionIdForwardsParentIdToParentService() {
		$childId = $this->newChildId();
		$instance = $this->newInstance( 'getLatestRevisionId', $childId->getParentId() );
		$instance->getLatestRevisionId( $childId );
	}

	/**
	 * @param string $parentMethod
	 * @param EntityId $expectedEntityId
	 *
	 * @return HierarchicalEntityRevisionLookup
	 */
	private function newInstance( $parentMethod, EntityId $expectedEntityId ) {
		$parentService = $this->getMock( EntityRevisionLookup::class );
		$parentService->expects( $this->once() )
			->method( $parentMethod )
			->with( $expectedEntityId );

		return new HierarchicalEntityRevisionLookup( $parentService );
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
