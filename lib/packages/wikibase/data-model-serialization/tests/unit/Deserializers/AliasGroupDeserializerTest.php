<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Deserializers\AliasGroupDeserializer;
use Wikibase\DataModel\Term\AliasGroup;

/**
 * @covers Wikibase\DataModel\Deserializers\AliasGroupDeserializer
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class AliasGroupDeserializerTest extends DeserializerBaseTest {

	/**
	 * @return Deserializer
	 */
	public function buildDeserializer() {
		return new AliasGroupDeserializer();
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
		);
	}

	/**
	 * @return array[] things that aren't deserialized by the deserializer
	 */
	public function nonDeserializableProvider() {
		return array(
			'multipleAliasGroups' => array( 'en' => array( 'A' ), 'de' => array( 'B' ) ),
		);
	}

	/**
	 * @return array[] an array of array( object deserialized, serialization )
	 */
	public function deserializationProvider() {
		return array(
			array(
				new AliasGroup( 'en', array() ),
				array( 'en' => array() ),
			),
			array(
				new AliasGroup( 'en', array( 'A' ) ),
				array( 'en' => array(
					array( 'language' => 'en', 'value' => 'A' ),
				) ),
			),
			array(
				new AliasGroup( 'en', array( 'A', 'B' ) ),
				array( 'en' => array(
					array( 'language' => 'en', 'value' => 'A' ),
					array( 'language' => 'en', 'value' => 'B' ),
				) ),
			),
		);
	}

}
