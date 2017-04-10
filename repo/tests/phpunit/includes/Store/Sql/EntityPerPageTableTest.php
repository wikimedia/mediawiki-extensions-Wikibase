<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Repo\Store\Sql\EntityPerPageTable;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * @covers Wikibase\Repo\Store\Sql\EntityPerPageTable
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @group medium
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
class EntityPerPageTableTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'wb_entity_per_page';
	}

	public function testAddEntityPage() {
		$epp = $this->newEntityPerPageTable();
		$epp->clear();

		$entityId = new ItemId( 'Q5' );
		$epp->addEntityPage( $entityId, 55 );

		$this->assertEquals( 55, $this->getPageIdForEntityId( $entityId ) );
	}

	public function testAddEntityPage_notInsertedTwice() {
		$epp = $this->newEntityPerPageTable();
		$epp->clear();

		$entityId = new ItemId( 'Q42' );
		$epp->addEntityPage( $entityId, 123 );

		// Get a LoadBalancer that makes sure we do the initial select
		// but don't try to (re-)insert.
		$db = $this->getMockBuilder( IDatabase::class )
			->disableOriginalConstructor()
			->enableProxyingToOriginalMethods()
			->setProxyTarget( wfGetDB( DB_MASTER ) )
			->getMockForAbstractClass();

		$db->expects( $this->never() )
			->method( 'insert' );

		$loadBalancer = $this->getMockBuilder( LoadBalancer::class )
			->disableOriginalConstructor()
			->getMock();

		$loadBalancer->expects( $this->once() )
			->method( 'getConnection' )
			->with( DB_MASTER )
			->will( $this->returnValue( $db ) );

		$epp = new EntityPerPageTable(
			$loadBalancer,
			new ItemIdParser()
		);
		$epp->addEntityPage( $entityId, 123 );

		$this->assertEquals( 123, $this->getPageIdForEntityId( $entityId ) );
	}

	public function testAddRedirectPage() {
		$epp = $this->newEntityPerPageTable();
		$epp->clear();

		$redirectId = new ItemId( 'Q5' );
		$targetId = new ItemId( 'Q10' );
		$epp->addRedirectPage( $redirectId, 55, $targetId );

		$this->assertEquals( 55, $this->getPageIdForEntityId( $redirectId ) );
	}

	/**
	 * @param EntityDocument[] $entities
	 * @param EntityRedirect[] $redirects
	 *
	 * @return EntityPerPageTable
	 */
	private function newEntityPerPageTable( array $entities = array(), array $redirects = array() ) {
		$table = new EntityPerPageTable(
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			new ItemIdParser()
		);
		$table->clear();

		foreach ( $entities as $entity ) {
			$pageId = $entity->getId()->getNumericId();
			$table->addEntityPage( $entity->getId(), $pageId );
		}

		foreach ( $redirects as $redirect ) {
			$pageId = $redirect->getEntityId()->getNumericId();
			$table->addRedirectPage( $redirect->getEntityId(), $pageId, $redirect->getTargetId() );
		}

		return $table;
	}

	private function getPageIdForEntityId( EntityId $entityId ) {
		$dbr = wfGetDB( DB_REPLICA );

		$row = $dbr->selectRow(
			'wb_entity_per_page',
			array( 'epp_page_id' ),
			array(
				'epp_entity_type' => $entityId->getEntityType(),
				'epp_entity_id' => $entityId->getNumericId()
			),
			__METHOD__
		);

		if ( !$row ) {
			return false;
		}

		return (int)$row->epp_page_id;
	}

}
