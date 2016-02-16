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
 * @author Addshore
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AliasGroupListDeserializerTest extends DeserializerBaseTest {

	/**
	 * @return Deserializer
	 */
	public function buildDeserializer() {
		return new AliasGroupListDeserializer();
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
			'must be an array of arrays' => array( array(
				'en' => new \stdClass(),
			) ),
			'array key must match' => array( array(
				'en' => array( array( 'language' => 'de', 'value' => 'Evil language' ) ),
			) ),
			'must be an array of arrays of arrays' => array( array(
				'en' => array( 'A' ),
				'de' => array( 'B' ),
			) ),
			'must contain language' => array( array(
				'en' => array( array( 'value' => 'foo' ) ),
			) ),
			'must contain value' => array( array(
				'en' => array( array( 'language' => 'en' ) ),
			) ),
			'must not contain source' => array( array(
				'en' => array(
					array( 'language' => 'en', 'value' => 'Evil language', 'source' => 'fallback' ),
				),
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
