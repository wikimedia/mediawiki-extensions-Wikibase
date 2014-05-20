<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityFactory;
use Wikibase\EntityRevision;
use Wikibase\EntityRevisionLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\EntityContentDataCodec;
use Wikibase\WikiPageEntityLookup;

/**
 * @covers Wikibase\WikiPageEntityLookup
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseEntityLookup
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikipageEntityLookupTest extends EntityRevisionLookupTest {

	/**
	 * @var EntityRevision[]
	 */
	protected static $testEntities = array();

	public function setUp( ) {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Only works on the repository (can't do foreign db access in unit tests)." );
		}

		parent::setUp();
	}

	protected static function storeTestEntity( Entity $entity ) {
		global $wgUser;

		//FIXME: We are using WikibaseRepo here, which is not available on the client.
		//      For now, this test case will only work on the repository.

		if ( !defined( 'WB_VERSION' ) ) {
			throw new \MWException( "Can't generate test entities in a client database." );
		}

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$revision = $store->saveEntity( $entity, "storeTestEntity", $wgUser );

		return $revision;
	}

	/**
	 * @see EntityRevisionLookupTest::newEntityRevisionLookup(newEntityLookup
	 *
	 * @param EntityRevision[] $entityRevisions
	 *
	 * @return EntityRevisionLookup
	 */
	protected function newEntityRevisionLookup( array $entityRevisions ) {
		// make sure all test entities are in the database.
		/* @var EntityRevision $entityRev */
		foreach ( $entityRevisions as $entityRev ) {
			$logicalRev = $entityRev->getRevision();

			if ( !isset( self::$testEntities[$logicalRev] ) ) {
				$rev = self::storeTestEntity( $entityRev->getEntity() );
				self::$testEntities[$logicalRev] = $rev;
			}
		}

		return new WikiPageEntityLookup(
			$this->getEntityContentCodec(),
			$this->getEntityFactory(),
			false );
	}

	protected function resolveLogicalRevision( $revision ) {
		if ( is_int( $revision ) && isset( self::$testEntities[$revision] ) ) {
			$revision = self::$testEntities[$revision]->getRevision();
		}

		return $revision;
	}

	private function getEntityContentCodec() {
		return new EntityContentDataCodec();
	}

	private function getEntityFactory() {
		return new EntityFactory( array(
			Item::ENTITY_TYPE => '\Wikibase\Item',
			Property::ENTITY_TYPE => '\Wikibase\Property',
		) );
	}
}
