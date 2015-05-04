<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\Store\SQL\EntityPerPageTable;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Store\SQL\EntityPerPageTable
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 * @group WikibaseEntityPerPage
 * @group Database
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
class EntityPerPageTableTest extends \MediaWikiTestCase {

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->tablesUsed[] = 'wb_entity_per_page';
	}

	public function testAddEntityPage() {
		$epp = $this->newEntityPerPageTable();
		$epp->clear();

		$entityId = new ItemId( 'Q5' );
		$epp->addEntityPage( $entityId, 55 );

		$this->assertEquals( 55, $epp->getPageIdForEntityId( $entityId ) );
	}

	public function testAddRedirectPage() {
		if ( !$this->isRedirectTargetColumnSupported() ) {
			$this->markTestSkipped( 'Redirects not supported' );
		}

		$epp = $this->newEntityPerPageTable();
		$epp->clear();

		$redirectId = new ItemId( 'Q5' );
		$targetId = new ItemId( 'Q10' );
		$epp->addRedirectPage( $redirectId, 55, $targetId );

		$this->assertEquals( $targetId, $epp->getRedirectForEntityId( $redirectId ) );
		$this->assertEquals( 55, $epp->getPageIdForEntityId( $redirectId ) );

		$ids = $epp->listEntities( Item::ENTITY_TYPE, 10 );
		$this->assertEmpty( $ids, 'Redirects must not show up in ID listings' );
	}

	protected function isRedirectTargetColumnSupported() {
		return WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'useRedirectTargetColumn' );
	}

	/**
	 * @param Entity[] $entities
	 * @param EntityRedirect[] $redirects
	 *
	 * @return EntityPerPageTable
	 */
	protected function newEntityPerPageTable( array $entities = array(), array $redirects = array() ) {
		$useRedirectTargetColumn = $this->isRedirectTargetColumnSupported();
		$idParser = new BasicEntityIdParser();

		$table = new EntityPerPageTable( $idParser, $useRedirectTargetColumn );
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

	protected function getIdStrings( array $entities ) {
		$ids = array_map( function ( $entity ) {
			if ( $entity instanceof EntityDocument ) {
				$entity = $entity->getId();
			} elseif ( $entity instanceof EntityRedirect ) {
				$entity = $entity->getEntityId();
			}

			return $entity->getSerialization();
		}, $entities );

		return $ids;
	}

	protected function assertEqualIds( array $expected,array $actual, $msg = null ) {
		$expectedIds = $this->getIdStrings( $expected );
		$actualIds = $this->getIdStrings( $actual );

		$this->assertArrayEquals( $expectedIds, $actualIds, false );
	}

	/**
	 * @dataProvider listEntitiesProvider
	 */
	public function testListEntities( array $entities, array $redirects, $type, $limit, $after, $redirectMode, array $expected ) {
		$table = $this->newEntityPerPageTable( $entities, $redirects );

		$actual = $table->listEntities( $type, $limit, $after, $redirectMode );

		$this->assertEqualIds( $expected, $actual );
	}

	public function listEntitiesProvider() {
		$property = new Property( new PropertyId( 'P1' ), null, 'string' );
		$item = new Item( new ItemId( 'Q5' ) );
		$redirect = new EntityRedirect( new ItemId( 'Q55' ), new ItemId( 'Q5' ) );

		return array(
			'empty' => array(
				array(),
				array(),
				null,
				100,
				null,
				EntityPerPage::NO_REDIRECTS,
				array()
			),
			'some entities' => array(
				array( $item, $property ),
				array( $redirect ),
				null,
				100,
				null,
				EntityPerPage::NO_REDIRECTS,
				array( $property, $item )
			),
			'entities after' => array(
				array( $item, $property ),
				array( $redirect ),
				null,
				100,
				$property->getId(),
				EntityPerPage::NO_REDIRECTS,
				array( $item )
			),
			'include redirects' => array(
				array( $item, $property ),
				array( $redirect ),
				null,
				100,
				null,
				EntityPerPage::INCLUDE_REDIRECTS,
				array( $property, $item, $redirect )
			),
			'only redirects' => array(
				array( $item, $property ),
				array( $redirect ),
				null,
				100,
				null,
				EntityPerPage::ONLY_REDIRECTS,
				array( $redirect )
			),
			'just properties' => array(
				array( $item, $property ),
				array( $redirect ),
				Property::ENTITY_TYPE,
				100,
				null,
				EntityPerPage::NO_REDIRECTS,
				array( $property )
			),
			'limit' => array(
				array( $item, $property ),
				array( $redirect ),
				Property::ENTITY_TYPE,
				1,
				null,
				EntityPerPage::NO_REDIRECTS,
				array( $property ) // current sort order is by numeric id, then type.
			),
			'no matches' => array(
				array( $property ),
				array( $redirect ),
				Item::ENTITY_TYPE,
				100,
				null,
				EntityPerPage::NO_REDIRECTS,
				array()
			),
		);
	}

	public function testGetPageIdForEntityId() {
		$entity = new Item( new ItemId( 'Q5' ) );

		$epp = $this->newEntityPerPageTable( array( $entity ) );
		$entityId = $entity->getId();

		$this->assertFalse( $epp->getPageIdForEntityId( new ItemId( 'Q7435389457' ) ) );

		$pageId = $epp->getPageIdForEntityId( $entityId );
		$this->assertInternalType( 'int', $pageId );
		$this->assertGreaterThan( 0, $pageId );
	}

	public function testGetRedirectForEntityId() {
		if ( !$this->isRedirectTargetColumnSupported() ) {
			$this->markTestSkipped( 'Redirects not supported' );
		}

		$entity = new Item( new ItemId( 'Q1' ) );
		$entity2 = new Item( new ItemId( 'Q2' ) );

		$epp = $this->newEntityPerPageTable( array( $entity, $entity2 ) );
		$redirectId = $entity->getId();
		$targetId = $entity2->getId();

		$redirectPageId = $epp->getPageIdForEntityId( $redirectId );
		$epp->addRedirectPage( $redirectId, $redirectPageId, $targetId );

		$this->assertFalse( $epp->getRedirectForEntityId( new ItemId( 'Q7435389457' ) ) );
		$this->assertNull( $epp->getRedirectForEntityId( $targetId ) );

		$targetIdFromEpp = $epp->getRedirectForEntityId( $redirectId );
		$this->assertEquals( $targetId, $targetIdFromEpp );
	}

}
