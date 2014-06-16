<?php

namespace Wikibase\Test;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\EntityRevision;
use Wikibase\InternalSerialization\DeserializerFactory;
use Wikibase\InternalSerialization\SerializerFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\Store\WikiPageEntityLookup;
use Wikibase\Lib\Store\EntityContentDataCodec;

/**
 * @covers Wikibase\Lib\Store\WikiPageEntityLookup
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
	private static $testEntities = array();

	protected static function storeTestEntity( Entity $entity ) {
		global $wgUser;

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
			false
		);
	}

	protected function resolveLogicalRevision( $revision ) {
		if ( is_int( $revision ) && isset( self::$testEntities[$revision] ) ) {
			$revision = self::$testEntities[$revision]->getRevision();
		}

		return $revision;
	}

	private function getEntityContentCodec() {
		$idParser = new BasicEntityIdParser();
		$serializerFactory = new SerializerFactory( new DataValueSerializer() );
		$deserializerFactory = new DeserializerFactory( new DataValueDeserializer( $GLOBALS['evilDataValueMap'] ), $idParser );

		$codec = new EntityContentDataCodec(
			$idParser,
			$serializerFactory->newEntitySerializer(),
			$deserializerFactory->newEntityDeserializer()
		);

		return $codec;
	}

}
