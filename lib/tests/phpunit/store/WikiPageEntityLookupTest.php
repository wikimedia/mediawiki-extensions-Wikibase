<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\EntityRevisionLookup;
use Wikibase\EntityRevision;
use Wikibase\Repo\WikibaseRepo;
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
		//NOTE: We are using EntityContent here, which is not available on the client.
		//      For now, this test case will only work on the repository.

		if ( !defined( 'WB_VERSION' ) ) {
			throw new \MWException( "Can't generate test entities in a client database." );
		}

		// FIXME: this is using repo functionality
		$content = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->newFromEntity( $entity );
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

	/**
	 * @see EntityRevisionLookupTest::newEntityRevisionLookup(newEntityLookup
	 *
	 * @param EntityRevision[] $entitieswiki
	 *
	 * @return EntityRevisionLookup
	 */
	protected function newEntityRevisionLookup( array $entities ) {
		// make sure all test entities are in the database.
		/* @var EntityRevision $entityRev */
		foreach ( $entities as $entityRev ) {
			$logicalRev = $entityRev->getRevision();

			if ( !isset( self::$testEntities[$logicalRev] ) ) {
				$rev = self::storeTestEntity( $entityRev->getEntity() );
				self::$testEntities[$logicalRev] = $rev;
			}
		}

		return new WikiPageEntityLookup( false );
	}

	protected function resolveLogicalRevision( $revision ) {
		if ( is_int( $revision ) && isset( self::$testEntities[$revision] ) ) {
			$revision = self::$testEntities[$revision]->getRevision();
		}

		return $revision;
	}

}
