<?php

namespace Wikibase\Test;

use DataValues\Serializers\DataValueSerializer;
use RuntimeException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\InternalSerialization\SerializerFactory;
use Wikibase\Lib\Serializers\LegacyInternalEntitySerializer;

/**
 * @covers Wikibase\Lib\Serializers\LegacyInternalEntitySerializer
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LegacyInternalEntitySerializerTest extends \PHPUnit_Framework_TestCase {

	public function entityProvider() {
		$empty = Item::newEmpty();

		$withLabels = Item::newEmpty();
		$withLabels->setLabel( 'en', 'Hello' );
		$withLabels->setLabel( 'es', 'Holla' );

		return array(
			array( $empty ),
			array( $withLabels ),
		);
	}

	/**
	 * @dataProvider entityProvider
	 * @param Entity $entity
	 */
	public function testSerialize( Entity $entity ) {
		$serializer = new LegacyInternalEntitySerializer();
		$data = $serializer->serialize( $entity );

		$this->assertEquals( $entity->toArray(), $data );
	}

	public function legacyFormatBlobProvider() {
		$entity = Item::newEmpty();
		$entity->setId( new ItemId( 'Q12' ) );
		$entity->setLabel( 'en', 'Test' );

		// make legacy blob
		$legacySerializer = new LegacyInternalEntitySerializer();
		$oldBlob = json_encode( $legacySerializer->serialize( $entity ) );

		// fake ancient legacy blob:
		// replace "entity":["item",7] with "entity":"q7"
		$id = $entity->getId()->getSerialization();
		$veryOldBlob = preg_replace( '/"entity":\["\w+",\d+\]/', '"entity":"' . strtolower( $id ) . '"', $oldBlob );

		// sanity
		if ( $oldBlob == $veryOldBlob ) {
			throw new RuntimeException( 'Failed to fake very old serialization format based on oldish serialization format.' );
		}

		// make new style blob
		$newSerializerFactory = new SerializerFactory( new DataValueSerializer() );
		$newSerializer = $newSerializerFactory->newEntitySerializer();
		$newBlob = json_encode( $newSerializer->serialize( $entity ) );

		return array(
			'old serialization / ancient id format' => array( $veryOldBlob, CONTENT_FORMAT_JSON, true ),
			'old serialization / new silly id format' => array( $oldBlob, CONTENT_FORMAT_JSON, true ),
			'new serialization format' => array( $newBlob, CONTENT_FORMAT_JSON, false ),
		);
	}

	/**
	 * @dataProvider legacyFormatBlobProvider
	 */
	public function testIsBlobUsingLegacyFormat( $blob, $format, $expected ) {
		$actual = LegacyInternalEntitySerializer::isBlobUsingLegacyFormat( $blob, $format );
		$this->assertEquals( $expected, $actual );
	}

}
