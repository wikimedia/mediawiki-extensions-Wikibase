<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Entity;
use Wikibase\EntityContentFactory;
use Wikibase\EntityLookup;
use Wikibase\EntityRevision;
use Wikibase\WikiPageEntityLookup;

/**
 * @covers Wikibase\WikiPageEntityLookup
 *
 * @file
 * @since 0.3
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseEntityLookup
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikipageEntityLookupTest extends EntityLookupTest {

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

	/**
	 * @see EntityLookupTest::newEntityLoader()
	 *
	 * @return EntityLookup
	 */
	protected function newEntityLoader( array $entities ) {
		// make sure all test entities are in the database.
		/* @var Entity $entity */
		foreach ( $entities as $logicalRev => $entity ) {
			if ( !isset( self::$testEntities[$logicalRev] ) ) {
				$rev = self::storeTestEntity( $entity );
				self::$testEntities[$logicalRev] = $rev;
			}
		}

		return new WikiPageEntityLookup( false, CACHE_DB );
	}

	protected static function storeTestEntity( Entity $entity ) {
		//NOTE: We are using EntityContent here, which is not available on the client.
		//      For now, this test case will only work on the repository.

		if ( !defined( 'WB_VERSION' ) ) {
			throw new \MWException( "Can't generate test entities in a client database." );
		}

		// FIXME: this is using repo functionality
		$content = EntityContentFactory::singleton()->newFromEntity( $entity );
		$status = $content->save( "storeTestEntity" );

		if ( !$status->isOK() ) {
			throw new \MWException( "couldn't create " . $content->getTitle()->getFullText()
				. ":\n" . $status->getWikiText() );
		}

		return new EntityRevision(
			$entity,
			$content->getWikiPage()->getRevision()->getId(),
			$content->getWikiPage()->getRevision()->getTimestamp()
		);
	}

	protected function resolveLogicalRevision( $revision ) {
		if ( is_int( $revision ) && isset( self::$testEntities[$revision] ) ) {
				$revision = self::$testEntities[$revision]->getRevision();
		}

		return $revision;
	}

	/**
	 * @dataProvider provideGetEntity
	 *
	 * @param string|EntityId $id The entity to get
	 * @param bool|int $revision The revision to get (or null)
	 * @param bool|int $expectedRev The expected revision
	 * @param string|null     $expectException
	 */
	public function testGetEntityRevision( $id, $revision, $shouldExist, $expectException = null ) {
		if ( is_string( $id ) ) {
			$id = EntityId::newFromPrefixedId( $id );
		}

		if ( $expectException !== null ) {
			$this->setExpectedException( $expectException );
		}

		$revision = $this->resolveLogicalRevision( $revision );

		/**
		 * @var WikiPageEntityLookup $lookup
		 */
		$lookup = $this->getLookup();
		$entityRev = $lookup->getEntityRevision( $id, $revision );

		if ( $shouldExist ) {
			$this->assertNotNull( $entityRev, "ID " . $id->__toString() );
			$this->assertEquals(
				$id,
				$entityRev->getEntity()->getId()
			);

			if ( $revision > 0 ) {
				$this->assertEquals( $revision, $entityRev->getRevision() );
			}
		} else {
			$this->assertNull( $entityRev, "ID " . $id->__toString() );
		}
	}

}
