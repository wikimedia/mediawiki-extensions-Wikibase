<?php

namespace Wikibase\Lib\Store\Hierarchical;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @covers \Wikibase\Lib\Store\Hierarchical\HierarchicalEntityRevisionLookup
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class HierarchicalEntityRevisionLookupTest extends PHPUnit_Framework_TestCase {

	public function testGivenParentId_getEntityRevisionForwardsToParentService() {
		$parentService = $this->getMock( EntityRevisionLookup::class );
		$parentService->expects( $this->once() )
			->method( 'getEntityRevision' );

		$instance = new HierarchicalEntityRevisionLookup( $parentService );
		$instance->getEntityRevision( $this->newParentId() );
	}

	public function testGivenHierarchicalId_getEntityRevisionReturnsParent() {
		$this->markTestIncomplete();
	}

	public function testGivenParentId_getLatestRevisionIdForwardsToParentService() {
		$parentService = $this->getMock( EntityRevisionLookup::class );
		$parentService->expects( $this->once() )
			->method( 'getLatestRevisionId' );

		$instance = new HierarchicalEntityRevisionLookup( $parentService );
		$instance->getLatestRevisionId( $this->newParentId() );
	}

	public function testGivenHierarchicalId_getLatestRevisionIdReturnsParent() {
		$this->markTestIncomplete();
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

}
