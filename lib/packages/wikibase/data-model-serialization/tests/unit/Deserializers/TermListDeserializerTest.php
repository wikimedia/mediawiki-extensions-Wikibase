<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Deserializers\TermListDeserializer;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Deserializers\TermListDeserializer
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class TermListDeserializerTest extends DeserializerBaseTest {

	/**
	 * @return Deserializer
	 */
	public function buildDeserializer() {
		return new TermListDeserializer( new TermDeserializer() );
	}

	/**
	 * @return array[] things that are deserialized by the deserializer
	 */
	public function deserializableProvider() {
		return array(
			array(
				'en' => array( 'language' => 'en', 'value' => 'FooBar' ),
			),
			array(
				'en' => array( 'language' => 'en', 'value' => 'FooBar' ),
				'de' => array( 'language' => 'de', 'value' => 'ItsALydia' ),
			),
		);
	}

	/**
	 * @return array[] things that aren't deserialized by the deserializer
	 */
	public function nonDeserializableProvider() {
		return array(
			array(
				'en' => array( 'language' => 'en', 'value' => 'FooBar', 'source' => 'fr' ),
			),
			array(
				'en' => array( 'language' => 'de', 'value' => 'Evil language' ),
			),
			array(
				'en' => array( 'language' => 'en' ),
			),
			array(
				8 => array( 'language' => 'en', 'value' => 'FooBar' ),
			),
		);
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
