<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Deserializers\TermListDeserializer;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Deserializers\TermListDeserializer
 *
 * @license GPL-2.0+
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

		$this->setExpectedException( DeserializationException::class );
		$deserializer->deserialize( $nonDeserializable );
	}

	/**
	 * @return array[] things that aren't deserialized by the deserializer
	 */
	public function nonDeserializableProvider() {
		return [
			'must be an array' => [ new \stdClass() ],
			'must be an array of arrays' => [ [
				'en' => new \stdClass(),
			] ],
			'must not contain source' => [ [
				'en' => [ 'language' => 'en', 'value' => 'FooBar', 'source' => 'fr' ],
			] ],
			'array key must match' => [ [
				'en' => [ 'language' => 'de', 'value' => 'Evil language' ],
			] ],
			'must contain value' => [ [
				'en' => [ 'language' => 'en' ],
			] ],
			'array key must be present' => [ [
				8 => [ 'language' => 'en', 'value' => 'FooBar' ],
			] ],
		];
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
		return [
			[
				new TermList( [] ),
				[],
			],
			[
				new TermList( [ new Term( 'en', 'Lama' ) ] ),
				[ 'en' => [ 'language' => 'en', 'value' => 'Lama' ] ],
			],
			[
				new TermList( [
					new Term( 'en', 'Lama' ),
					new Term( 'de', 'Delama' ),
				] ),
				[
					'en' => [ 'language' => 'en', 'value' => 'Lama' ],
					'de' => [ 'language' => 'de', 'value' => 'Delama' ],
				],
			],
		];
	}

}
