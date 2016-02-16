<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Term\Term;

/**
 * @covers Wikibase\DataModel\Deserializers\TermDeserializer
 *
 * @licence GNU GPL v2+
 * @author Addshore
 */
class TermDeserializerTest extends DeserializerBaseTest {

	/**
	 * @return Deserializer
	 */
	public function buildDeserializer() {
		return new TermDeserializer();
	}

	public function deserializableProvider() {
		return array( array() );
	}

	/**
	 * @return array[] things that aren't deserialized by the deserializer
	 */
	public function nonDeserializableProvider() {
		return array(
			'must be an array' => array( new \stdClass() ),
			'must contain language' => array( array(
				'value' => 'FooBar',
			) ),
			'must contain value' => array( array(
				'language' => 'de',
			) ),
			'language must be string' => array( array(
				'language' => 123,
				'value' => 'FooBar',
			) ),
			'value must be string' => array( array(
				'language' => 'de',
				'value' => 999,
			) ),
			'must not contain source' => array( array(
				'language' => 'fr',
				'value' => 'Fr to DE hehe',
				'source' => 'de',
			) ),
		);
	}

	/**
	 * @return array[] an array of array( object deserialized, serialization )
	 */
	public function deserializationProvider() {
		return array(
			array(
				new Term( 'en', 'Value' ),
				array(
					'language' => 'en',
					'value' => 'Value',
				),
			),
		);
	}

}
