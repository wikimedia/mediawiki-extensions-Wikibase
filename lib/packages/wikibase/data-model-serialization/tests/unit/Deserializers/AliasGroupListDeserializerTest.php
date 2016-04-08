<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Deserializers\AliasGroupListDeserializer;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @covers Wikibase\DataModel\Deserializers\AliasGroupListDeserializer
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AliasGroupListDeserializerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testDeserializeThrowsDeserializationException( $nonDeserializable ) {
		$deserializer = new AliasGroupListDeserializer();
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
	 * @dataProvider deserializationProvider
	 */
	public function testDeserialization( $object, $serialization ) {
		$deserializer = new AliasGroupListDeserializer();
		$this->assertEquals( $object, $deserializer->deserialize( $serialization ) );
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
