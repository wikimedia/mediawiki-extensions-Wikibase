<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Deserializers\AliasGroupListDeserializer;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @covers Wikibase\DataModel\Deserializers\AliasGroupListDeserializer
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AliasGroupListDeserializerTest extends DeserializerBaseTest {

	/**
	 * @return Deserializer
	 */
	public function buildDeserializer() {
		return new AliasGroupListDeserializer();
	}

	/**
	 * @return array[] things that are deserialized by the deserializer
	 */
	public function deserializableProvider() {
		return array(
			array( 'en' => array() ),
			array( 'de' => array(
				array( 'language' => 'de', 'value' => 'One' ),
				array( 'language' => 'de', 'value' => 'Pony' ),
			) ),
			array(
				'de' => array( array( 'language' => 'de', 'value' => 'foo' ) ),
				'en' => array( array( 'language' => 'en', 'value' => 'bar' ) ),
			),
		);
	}

	/**
	 * @return array[] things that aren't deserialized by the deserializer
	 */
	public function nonDeserializableProvider() {
		return array(
			array( 'en' => array(
				array( 'language' => 'de', 'value' => 'Evil language' )
			) ),
			array( 'en' => array( 'A' ), 'de' => array( 'B' ) ),
			array( 'en' => array(
				array( 'language' => 'en', 'value' => 'Evil language', 'source' => 'fallback' )
			) ),
		);
	}

	/**
	 * @return array[] an array of array( object deserialized, serialization )
	 */
	public function deserializationProvider() {
		return array(
			array(
				new AliasGroupList( array( new AliasGroup( 'en', array() ) ) ),
				array( 'en' => array() ),
			),
			array(
				new AliasGroupList( array( new AliasGroup( 'en', array( 'A' ) ) ) ),
				array( 'en' => array(
					array( 'language' => 'en', 'value' => 'A' ),
				) ),
			),
			array(
				new AliasGroupList( array( new AliasGroup( 'en', array( 'A', 'B' ) ) ) ),
				array( 'en' => array(
					array( 'language' => 'en', 'value' => 'A' ),
					array( 'language' => 'en', 'value' => 'B' ),
				) ),
			),
		);
	}

}
