<?php

namespace Wikibase\Store\Test;

use Wikibase\Lib\Store\EntityContentDataCodec;
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

		$this->codec = new EntityContentDataCodec();
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
	public function testEncodeEntityContentData( array $data, $format ) {
		$blob = $this->codec->encodeEntityContentData( $data, $format );
		$this->assertType( 'string', $blob );

		$actual = $this->codec->decodeEntityContentData( $blob, $format );

		$this->assertEquals( $data, $actual, 'round trip' );
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
