<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * This test needs to be in repo, although the class is in lib as we can't alter
 * the data without repo functionality.
 *
 * @covers Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class WikiPageEntityMetaDataLookupTest extends MediaWikiTestCase {

	/**
	 * @var EntityRevision[]
	 */
	private $data = array();

	protected function setUp() {
		parent::setUp();

		if ( !$this->data ) {
			global $wgUser;

			$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
			for ( $i = 0; $i < 3; $i++ ) {
				$this->data[] = $store->saveEntity( new Item(), 'WikiPageEntityMetaDataLookupTest', $wgUser, EDIT_NEW );
			}

			$entity = $this->data[2]->getEntity();
			$entity->getFingerprint()->setLabel( 'en', 'Updated' );
			$this->data[2] = $store->saveEntity( $entity, 'WikiPageEntityMetaDataLookupTest', $wgUser );
		}
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup() {
		$titleLookup = $this->getMock( EntityTitleLookup::class );

		$titleLookup->expects( $this->any() )
			->method( 'getTitleForID' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::makeTitle(
					NS_MAIN,
					$id->getEntityType() . '/' . $id->getSerialization()
				);
			} ) );

		$titleLookup->expects( $this->any() )
			->method( 'getNamespaceForType' )
			->will( $this->returnValue( NS_MAIN ) );

		return $titleLookup;
	}

	/**
	 * @return WikiPageEntityMetaDataLookup
	 */
	private function getWikiPageEntityMetaDataLookup() {
		return new WikiPageEntityMetaDataLookup( $this->getEntityTitleLookup() );
	}

	public function testLoadRevisionInformationById_latest() {
		$entityRevision = $this->data[0];

		$result = $this->getWikiPageEntityMetaDataLookup()
			->loadRevisionInformationByRevisionId( $entityRevision->getEntity()->getId(), $entityRevision ->getRevisionId() );

		$this->assertEquals( $entityRevision->getRevisionId(), $result->rev_id );
		$this->assertEquals( $entityRevision->getRevisionId(), $result->page_latest );
	}

	public function testLoadRevisionInformationById_old() {
		$entityRevision = $this->data[2];

		$result = $this->getWikiPageEntityMetaDataLookup()
			->loadRevisionInformationByRevisionId(
				$entityRevision->getEntity()->getId(),
				$entityRevision ->getRevisionId() - 1 // There were two edits to this item in sequence
			);

		$this->assertEquals( $entityRevision->getRevisionId() - 1, $result->rev_id );
		// Page latest should reflect that this is not the latest revision
		$this->assertEquals( $entityRevision->getRevisionId(), $result->page_latest );
	}

	public function testLoadRevisionInformationById_wrongRevision() {
		$entityRevision = $this->data[2];

		$result = $this->getWikiPageEntityMetaDataLookup()
			->loadRevisionInformationByRevisionId(
				$entityRevision->getEntity()->getId(),
				$entityRevision ->getRevisionId() * 2 // Doesn't exist
			);

		$this->assertFalse( $result );
	}

	public function testLoadRevisionInformationById_notFound() {
		$result = $this->getWikiPageEntityMetaDataLookup()
			->loadRevisionInformationByRevisionId(
				new ItemId( 'Q823487354' ),
				823487354
			);

		$this->assertFalse( $result );
	}

	public function testLoadRevisionInformation() {
		$entityIds = array(
			$this->data[0]->getEntity()->getId(),
			$this->data[1]->getEntity()->getId(),
			new ItemId( 'Q823487354' ), // Doesn't exist
			$this->data[2]->getEntity()->getId()
		);

		$result = $this->getWikiPageEntityMetaDataLookup()
			->loadRevisionInformation( $entityIds, DB_SLAVE );

		$serializedEntityIds = array();
		foreach ( $entityIds as $entityId ) {
			$serializedEntityIds[] = $entityId->getSerialization();
		}

		// Verify that all requested entity ids are part of the result
		$this->assertEquals( $serializedEntityIds, array_keys( $result ) );

		// Verify revision ids
		$this->assertEquals(
			$result[$serializedEntityIds[0]]->rev_id, $this->data[0]->getRevisionId()
		);
		$this->assertEquals(
			$result[$serializedEntityIds[1]]->rev_id, $this->data[1]->getRevisionId()
		);
		$this->assertEquals(
			$result[$serializedEntityIds[3]]->rev_id, $this->data[2]->getRevisionId()
		);

		// Verify that Q823487354 (doesn't exist) is not part of the result
		$this->assertFalse( $result['Q823487354'] );
	}

}
