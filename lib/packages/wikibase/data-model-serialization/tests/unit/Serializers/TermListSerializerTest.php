<?php

namespace Tests\Wikibase\DataModel\Serializers;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Serializers\TermListSerializer;
use Wikibase\DataModel\Serializers\TermSerializer;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Serializers\TermListSerializer
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class TermListSerializerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param bool $useObjectsForMaps
	 *
	 * @return TermListSerializer
	 */
	private function buildSerializer( $useObjectsForMaps = false ) {
		return new TermListSerializer( new TermSerializer(), $useObjectsForMaps );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( TermList $input, $useObjectsForMaps, $expected ) {
		$serializer = $this->buildSerializer( $useObjectsForMaps );

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
		$serializer = $this->buildSerializer();
		$this->setExpectedException( 'Serializers\Exceptions\UnsupportedObjectException' );
		$serializer->serialize( new \stdClass() );
	}

	public function testTermListSerializerWithOptionObjectsForMaps() {
		$serializer = $this->buildSerializer( true );

		$terms = new TermList( array( new Term( 'en', 'foo' ) ) );

		$serial = new \stdClass();
		$serial->en = array(
			'language' => 'en',
			'value' => 'foo'
		);

		$this->assertEquals( $serial, $serializer->serialize( $terms ) );
	}

}
