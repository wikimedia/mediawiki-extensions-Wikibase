<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\WikiPageEntityRevisionLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Lib\Store\WikiPageEntityRevisionLookup
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseEntityLookup
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikiPageEntityRevisionLookupTest extends EntityRevisionLookupTest {

	/**
	 * Revision ids as used by the test to real revision id map.
	 *
	 * @var int[]
	 */
	private static $revisionIdMap = array();

	protected static function storeTestEntity( Entity $entity ) {
		global $wgUser;

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$revision = $store->saveEntity( $entity, "storeTestEntity", $wgUser );

		return $revision;
	}

	protected static function storeTestRedirect( EntityRedirect $redirect ) {
		global $wgUser;

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$revision = $store->saveRedirect( $redirect, "storeTestEntity", $wgUser );

		return $revision;
	}

	/**
	 * @see EntityLookupTest::newEntityLoader(newEntityLookup
	 *
	 * @param EntityRevision[] $entityRevisions
	 * @param EntityRedirect[] $entityRedirects Revision id => EntityRedirect
	 *
	 * @return EntityLookup
	 */
	protected function newEntityRevisionLookup( array $entityRevisions, array $entityRedirects ) {
		// make sure all test entities are in the database.

		foreach ( $entityRevisions as $entityRev ) {
			$logicalRev = $entityRev->getRevisionId();

			if ( !isset( self::$revisionIdMap[$logicalRev] ) ) {
				$rev = self::storeTestEntity( $entityRev->getEntity() );
				self::$revisionIdMap[$logicalRev] = $rev->getRevisionId();
			}
		}

		if ( $this->itemSupportsRedirect() ) {
			foreach ( $entityRedirects as $logicalRev => $entityRedir ) {
				if ( !isset( self::$revisionIdMap[$logicalRev] ) ) {
					$rev = self::storeTestRedirect( $entityRedir );
					self::$revisionIdMap[$logicalRev] = $rev;
				}
			}
		}

		return new WikiPageEntityRevisionLookup(
			WikibaseRepo::getDefaultInstance()->getEntityContentDataCodec(),
			new WikiPageEntityMetaDataLookup( new BasicEntityIdParser() ),
			false
		);
	}

	protected function resolveLogicalRevision( $revision ) {
		if ( isset( self::$revisionIdMap[$revision] ) ) {
			return self::$revisionIdMap[$revision];
		}

		return $revision;
	}

}
