<?php

namespace Wikibase\Store\Test;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\InternalSerialization\DeserializerFactory;
use Wikibase\InternalSerialization\SerializerFactory;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Test\EntityTestCase;

/**
 * @covers Wikibase\Lib\Store\EntityContentDataCodec
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityContentDataCodecTest extends EntityTestCase {

	/**
	 * @var EntityContentDataCodec
	 */
	private $codec;

	protected function setUp() {
		parent::setUp();

		$idParser = new BasicEntityIdParser();

		$serializerFactory = new SerializerFactory( new DataValueSerializer() );
		$deserializerFactory = new DeserializerFactory( new DataValueDeserializer( $GLOBALS['evilDataValueMap'] ), $idParser );

		$this->codec = new EntityContentDataCodec(
			$idParser,
			$serializerFactory->newEntitySerializer(),
			$deserializerFactory->newEntityDeserializer()
		);
	}

	public function entityIdProvider() {
		$q11 = new ItemId( 'Q11' );

		return array(
			'new style' => array( json_encode( array( 'entity' => 'Q11' ) ), $q11 ),
			'old style' => array( json_encode( array( 'entity' => array( 'item', 11 ) ) ), $q11 ),
		);
	}

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testEntityIdDecoding( $data, EntityId $id ) {
		$entity = $this->codec->decodeEntity( $data, CONTENT_FORMAT_JSON );
		$this->assertEquals( $id, $entity->getId() );
	}

	public function entityProvider() {
		$empty = Item::newEmpty();
		$empty->setId( new ItemId( 'Q1' ) );

		$simple = Item::newEmpty();
		$simple->setId( new ItemId( 'Q1' ) );
		$simple->setLabel( 'en', 'Test' );

		return array(
			'empty' => array( $empty, null ),
			'empty json' => array( $empty, CONTENT_FORMAT_JSON ),

			'simple' => array( $simple, null ),
			'simple json' => array( $simple, CONTENT_FORMAT_JSON ),
			'simple php' => array( $simple, CONTENT_FORMAT_SERIALIZED ),
		);
	}

	/**
	 * @dataProvider entityProvider
	 */
	public function testEncodeAndDecodeEntity( Entity $entity, $format ) {
		$blob = $this->codec->encodeEntity( $entity, $format );
		$this->assertType( 'string', $blob );

		$actual = $this->codec->decodeEntity( $blob, $format );
		$this->assertTrue( $entity->equals( $actual ), 'round trip' );
	}

	public function testGetDefaultFormat_isJson() {
		$defaultFormat = $this->codec->getDefaultFormat();
		$this->assertEquals( CONTENT_FORMAT_JSON, $defaultFormat );
	}

	public function testGetSupportedFormats() {
		$supportedFormats = $this->codec->getSupportedFormats();
		$this->assertType( 'array', $supportedFormats );
		$this->assertNotEmpty( $supportedFormats );
		$this->assertContainsOnly( 'string', $supportedFormats );
	}

	public function testGetSupportedFormats_containsDefaultFormat() {
		$supportedFormats = $this->codec->getSupportedFormats();
		$this->assertContains( $this->codec->getDefaultFormat(), $supportedFormats );
	}

}
