<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Serializers\TermListSerializer;
use Wikibase\DataModel\Serializers\TermSerializer;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Serializers\TermListSerializer
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class TermListSerializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( TermList $input, $useObjectsForMaps, $expected ) {
		$serializer = new TermListSerializer( new TermSerializer(), $useObjectsForMaps );

		$output = $serializer->serialize( $input );

		$this->assertEquals( $expected, $output );
	}

	public function serializationProvider() {
		return array(
			array(
				new TermList( array() ),
				false,
				array()
			),
			array(
				new TermList( array() ),
				true,
				new \stdClass()
			),
			array(
				new TermList( array(
					new Term( 'en', 'Water' ),
					new Term( 'it', 'Lama' ),
					new TermFallback( 'pt', 'Lama', 'de', 'zh' ),
				) ),
				false,
				array(
					'en' => array( 'language' => 'en', 'value' => 'Water' ),
					'it' => array( 'language' => 'it', 'value' => 'Lama' ),
					'pt' => array( 'language' => 'de', 'value' => 'Lama', 'source' => 'zh' ),
				)
			),
		);
	}

	public function testWithUnsupportedObject() {
		$serializer = new TermSerializer();
		$this->setExpectedException( 'Serializers\Exceptions\UnsupportedObjectException' );
		$serializer->serialize( new \stdClass() );
	}

	public function testTermListSerializerWithOptionObjectsForMaps() {
		$serializer = new TermListSerializer( new TermSerializer(), true );

		$terms = new TermList( array( new Term( 'en', 'foo' ) ) );

		$serial = new \stdClass();
		$serial->en = array(
			'language' => 'en',
			'value' => 'foo'
		);

		$this->assertEquals( $serial, $serializer->serialize( $terms ) );
	}

}
