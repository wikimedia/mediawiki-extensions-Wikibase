<?php

namespace Wikibase\Repo\Tests\Store;

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup;
use Wikibase\Lib\Tests\EntityRevisionLookupTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseEntityLookup
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikiPageEntityRevisionLookupTest extends EntityRevisionLookupTestCase {

	/**
	 * @var EntityRevision[]
	 */
	private static $testEntities = [];

	protected static function storeTestEntity( EntityDocument $entity ) {
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
	 * @see EntityRevisionLookupTestCase::newEntityRevisionLookup
	 *
	 * @param EntityRevision[] $entityRevisions
	 * @param EntityRedirect[] $entityRedirects
	 *
	 * @return EntityLookup
	 */
	protected function newEntityRevisionLookup( array $entityRevisions, array $entityRedirects ) {
		// make sure all test entities are in the database.

		foreach ( $entityRevisions as $entityRev ) {
			$logicalRev = $entityRev->getRevisionId();

			if ( !isset( self::$testEntities[$logicalRev] ) ) {
				$rev = self::storeTestEntity( $entityRev->getEntity() );
				self::$testEntities[$logicalRev] = $rev;
			}
		}

		foreach ( $entityRedirects as $entityRedir ) {
			self::storeTestRedirect( $entityRedir );
		}

		return new WikiPageEntityRevisionLookup(
			WikibaseRepo::getDefaultInstance()->getEntityContentDataCodec(),
			new WikiPageEntityMetaDataLookup( $this->getEntityNamespaceLookup() ),
			MediaWikiServices::getInstance()->getRevisionStore(),
			false
		);
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	private function getEntityNamespaceLookup() {
		$entityNamespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		return $entityNamespaceLookup;
	}

	protected function resolveLogicalRevision( $revision ) {
		if ( is_int( $revision ) && isset( self::$testEntities[$revision] ) ) {
			$revision = self::$testEntities[$revision]->getRevisionId();
		}

		return $revision;
	}

	public function testGetEntityRevision_byRevisionIdWithMode() {
		// Needed to fill the database.
		$this->newEntityRevisionLookup( $this->getTestRevisions(), [] );

		$testEntityRevision = reset( self::$testEntities );
		$entityId = $testEntityRevision->getEntity()->getId();
		$revisionId = $testEntityRevision->getRevisionId();

		$realMetaDataLookup = new WikiPageEntityMetaDataLookup( $this->getEntityNamespaceLookup() );
		$metaDataLookup = $this->getMockBuilder( WikiPageEntityMetaDataLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$metaDataLookup->expects( $this->once() )
			->method( 'loadRevisionInformationByRevisionId' )
			->with( $entityId, $revisionId, 'load-mode' )
			->will( $this->returnValue(
				$realMetaDataLookup->loadRevisionInformationByRevisionId( $entityId, $revisionId )
			) );

		$lookup = new WikiPageEntityRevisionLookup(
			WikibaseRepo::getDefaultInstance()->getEntityContentDataCodec(),
			$metaDataLookup,
			MediaWikiServices::getInstance()->getRevisionStore(),
			false
		);

		$entityRevision = $lookup->getEntityRevision( $entityId, $revisionId, 'load-mode' );

		$this->assertSame( $revisionId, $entityRevision->getRevisionId() );
	}

}
