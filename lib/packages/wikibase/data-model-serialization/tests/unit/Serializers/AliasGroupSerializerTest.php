<?php

namespace Tests\Wikibase\DataModel\Serializers;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Serializers\AliasGroupSerializer;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupFallback;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @covers Wikibase\DataModel\Serializers\AliasGroupSerializer
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AliasGroupSerializerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider nonSerializableProvider
	 */
	public function testSerializeThrowsUnsupportedObjectException( $nonSerializable ) {
		$serializer = new AliasGroupSerializer();
		$this->setExpectedException( 'Serializers\Exceptions\UnsupportedObjectException' );
		$serializer->serialize( $nonSerializable );
	}

	public function nonSerializableProvider() {
		return array(
			array(
				5
			),
			array(
				array()
			),
			array(
				new AliasGroupList()
			)
		);
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( $serialization, $object ) {
		$serializer = new AliasGroupSerializer();
		$this->assertSame( $serialization, $serializer->serialize( $object ) );
	}

	public function serializationProvider() {
		return array(
			array(
				array(),
				new AliasGroup( 'en', array() )
			),
			array(
				array(
					array( 'language' => 'en', 'value' => 'One' )
				),
				new AliasGroup( 'en', array( 'One' ) )
			),
			array(
				array(
					array( 'language' => 'en', 'value' => 'One' ),
					array( 'language' => 'en', 'value' => 'Pony' )
				),
				new AliasGroup( 'en', array( 'One', 'Pony' ) )
			),
			array(
				array(
					array( 'language' => 'de', 'value' => 'One', 'source' => 'fr' ),
					array( 'language' => 'de', 'value' => 'Pony', 'source' => 'fr' ),
				),
				new AliasGroupFallback( 'en', array( 'One', 'Pony' ), 'de', 'fr' )
			)
		);
	}

}
