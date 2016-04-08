<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Deserializers\TermListDeserializer;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Deserializers\TermListDeserializer
 *
 * @licence GNU GPL v2+
 * @author Addshore
 */
class TermListDeserializerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return Deserializer
	 */
	private function buildDeserializer() {
		return new TermListDeserializer( new TermDeserializer() );
	}

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testDeserializeThrowsDeserializationException( $nonDeserializable ) {
		$deserializer = $this->buildDeserializer();
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$deserializer->deserialize( $nonDeserializable );
	}

	/**
	 * @return array[] things that aren't deserialized by the deserializer
	 */
	public function nonDeserializableProvider() {
		return array(
			'must be an array' => array( new \stdClass() ),
			'must be an array of arrays' => array( array(
				'en' => new \stdClass(),
			) ),
			'must not contain source' => array( array(
				'en' => array( 'language' => 'en', 'value' => 'FooBar', 'source' => 'fr' ),
			) ),
			'array key must match' => array( array(
				'en' => array( 'language' => 'de', 'value' => 'Evil language' ),
			) ),
			'must contain value' => array( array(
				'en' => array( 'language' => 'en' ),
			) ),
			'array key must be present' => array( array(
				8 => array( 'language' => 'en', 'value' => 'FooBar' ),
			) ),
		);
	}

	/**
	 * @dataProvider deserializationProvider
	 */
	public function testDeserialization( $object, $serialization ) {
		$this->assertEquals( $object, $this->buildDeserializer()->deserialize( $serialization ) );
	}

	/**
	 * @return array[] an array of array( object deserialized, serialization )
	 */
	public function deserializationProvider() {
		return array(
			array(
				new TermList( array() ),
				array(),
			),
			array(
				new TermList( array( new Term( 'en', 'Lama' ) ) ),
				array( 'en' => array( 'language' => 'en', 'value' => 'Lama' ) ),
			),
			array(
				new TermList( array(
					new Term( 'en', 'Lama' ),
					new Term( 'de', 'Delama' ),
				) ),
				array(
					'en' => array( 'language' => 'en', 'value' => 'Lama' ),
					'de' => array( 'language' => 'de', 'value' => 'Delama' ),
				),
			),
		);
	}

}
