<?php

namespace Wikibase\Test;

use Wikibase\Query;
use Wikibase\ArrayStructureCodec;

/**
 * @covers ArrayStructureCodec
 *
 * @since 0.5
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ArrayStructureCodecTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider serializationProvider
	 */
	public function testRoundTrip( $data, $format ) {
		$codec = new ArrayStructureCodec();

		$blob = $codec->serializeData( $data, $format );
		$this->assertInternalType( 'string', $blob );

		$actual = $codec->unserializeData( $blob, $format );
		$this->assertEquals( $data, $actual );
	}

	public function serializationProvider() {
		$structures = array(
			array(),
			array( 'foo', 'bar' ),
			array( 'foo' => 3, 'bar' => 25 ),
			array( 'foo', 'bar' => array( 22, 23, 24 ) ),
		);

		$formats = ArrayStructureCodec::getSupportedFormats();

		$cases = array();

		foreach ( $structures as $structure ) {
			foreach ( $formats as $format ) {
				$cases[] = array( $structure, $format );
			}
		}

		return $cases;
	}

	/**
	 * @dataProvider serializationFailurProvider
	 */
	public function testSerializationFailure( $data, $format, $error ) {
		$codec = new ArrayStructureCodec();

		$this->setExpectedException( $error );
		$codec->serializeData( $data, $format );
	}

	public function serializationFailurProvider() {
		return array(
			array( array(), CONTENT_FORMAT_TEXT, 'MWException' ),
			array( 13, CONTENT_FORMAT_JSON, 'InvalidArgumentException' ),
			array( "foo", CONTENT_FORMAT_JSON, 'InvalidArgumentException' ),
			array( null, CONTENT_FORMAT_JSON, 'InvalidArgumentException' ),
		);
	}

	/**
	 * @dataProvider unserializationFailurProvider
	 */
	public function testUnserializationFailure( $data, $format, $error ) {
		$codec = new ArrayStructureCodec();

		$this->setExpectedException( $error );
		$codec->unserializeData( $data, $format );
	}

	public function unserializationFailurProvider() {
		return array(
			array( '{}', CONTENT_FORMAT_TEXT, 'MWException' ),
			array( array(), CONTENT_FORMAT_JSON, 'InvalidArgumentException' ),
			array( 13, CONTENT_FORMAT_JSON, 'InvalidArgumentException' ),
			array( "foo", CONTENT_FORMAT_JSON, 'MWContentSerializationException' ),
			array( null, CONTENT_FORMAT_JSON, 'InvalidArgumentException' ),
		);
	}

}

