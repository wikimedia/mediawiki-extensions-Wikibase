<?php

namespace Wikibase\Store\Test;
use Wikibase\Store\EntityContentDataCodec;
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

	protected function getCodec() {
		return new EntityContentDataCodec();
	}

	public function encodeDecodeProvider() {
		return array(
			'empty' => array( array(), null ),
			'empty json' => array( array(), CONTENT_FORMAT_JSON ),

			'list' => array( array( 'a', 'b', 'c' ), null ),
			'list json' => array( array( 'a', 'b', 'c' ), CONTENT_FORMAT_JSON ),
		);
	}

	/**
	 * @dataProvider encodeDecodeProvider
	 */
	public function testEncodeEntityContentData( $data, $format ) {
		$codec = $this->getCodec();

		$blob = $codec->encodeEntityContentData( $data, $format );
		$this->assertType( 'string', $blob );

		$actual = $codec->decodeEntityContentData( $blob, $format );

		$this->assertEquals( $data, $actual, 'round trip' );
	}

	public function testGetDefaultFormat() {
		$codec = $this->getCodec();

		$this->assertType( 'string', $codec->getDefaultFormat() );
		$this->assertContains( $codec->getDefaultFormat(), $codec->getSupportedFormats() );
	}

	public function testGetSupportedFormats() {
		$codec = $this->getCodec();

		$supported = $codec->getSupportedFormats();
		$this->assertType( 'array', $supported );
		$this->assertContains( CONTENT_FORMAT_JSON, $codec->getSupportedFormats() );
	}

}
