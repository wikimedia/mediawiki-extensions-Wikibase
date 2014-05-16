<?php

namespace Wikibase\Store\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\EntityFactory;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Test\EntityTestCase;

/**
 * @covers Wikibase\Serializers\EntityContentCodec
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

		$entityClasses = array(
			Item::ENTITY_TYPE => '\Wikibase\Item',
			Property::ENTITY_TYPE => '\Wikibase\Property',
		);

		$this->codec = new EntityContentDataCodec(
			new BasicEntityIdParser(),
			new EntityFactory( $entityClasses )
		);
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

	public function redirectProvider() {
		$q6 = new ItemId( 'Q6' );
		$q8 = new ItemId( 'Q8' );

		$redirect = new EntityRedirect( $q6, $q8 );

		return array(
			'redirect' => array( $redirect, null ),
			'empty json' => array( $redirect, CONTENT_FORMAT_JSON ),
		);
	}

	/**
	 * @dataProvider redirectProvider
	 */
	public function testEncodeAndDecodeRedirect( EntityRedirect $redirect, $format ) {
		$blob = $this->codec->encodeRedirect( $redirect, $format );
		$this->assertType( 'string', $blob );

		$actual = $this->codec->decodeRedirect( $blob, $format );
		$this->assertTrue( $redirect->equals( $actual ), 'round trip' );
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
