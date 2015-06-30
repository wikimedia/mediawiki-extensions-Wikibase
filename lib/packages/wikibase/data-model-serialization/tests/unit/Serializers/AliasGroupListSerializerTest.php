<?php

namespace Tests\Wikibase\DataModel\Serializers;

use stdClass;
use Wikibase\DataModel\Serializers\AliasGroupListSerializer;
use Wikibase\DataModel\Serializers\TermSerializer;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupFallback;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @covers Wikibase\DataModel\Serializers\AliasGroupListSerializer
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AliasGroupListSerializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( AliasGroupList $input, $useObjectsForMaps, $expected ) {
		$serializer = new AliasGroupListSerializer( $useObjectsForMaps );

		$output = $serializer->serialize( $input );

		$this->assertEquals( $expected, $output );
	}

	public function serializationProvider() {
		return array(
			array(
				new AliasGroupList( array( new AliasGroup( 'en', array() ) ) ),
				false,
				array(),
			),
			array(
				new AliasGroupList( array( new AliasGroup( 'en', array() ) ) ),
				true,
				new \stdClass()
			),
			array(
				new AliasGroupList( array( new AliasGroup( 'en', array( 'One' ) ) ) ),
				false,
				array( 'en' => array(
					array( 'language' => 'en', 'value' => 'One' ),
				) ),
			),
			array(
				new AliasGroupList( array( new AliasGroup( 'en', array( 'One', 'Pony' ) ) ) ),
				false,
				array( 'en' => array(
					array( 'language' => 'en', 'value' => 'One' ),
					array( 'language' => 'en', 'value' => 'Pony' ),
				) ),
			),
			array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'One', 'Pony' ) ),
					new AliasGroup( 'de', array( 'foo', 'bar' ) )
				) ),
				false,
				array( 'en' => array(
					array( 'language' => 'en', 'value' => 'One' ),
					array( 'language' => 'en', 'value' => 'Pony' ),
				), 'de' => array(
					array( 'language' => 'de', 'value' => 'foo' ),
					array( 'language' => 'de', 'value' => 'bar' ),
				) ),
			),
			array(
				new AliasGroupList( array( new AliasGroupFallback( 'en', array( 'One', 'Pony' ), 'de', 'fr' ) ) ),
				false,
				array( 'en' => array(
					array( 'language' => 'de', 'value' => 'One', 'source' => 'fr' ),
					array( 'language' => 'de', 'value' => 'Pony', 'source' => 'fr' ),
				) ),
			),
		);
	}

	public function testWithUnsupportedObject() {
		$serializer = new TermSerializer();
		$this->setExpectedException( 'Serializers\Exceptions\UnsupportedObjectException' );
		$serializer->serialize( new stdClass() );
	}

	public function testAliasGroupListSerializerWithOptionObjectsForMaps() {
		$serializer = new AliasGroupListSerializer( true );

		$aliases = new AliasGroupList( array( new AliasGroup( 'en', array( 'foo', 'bar' ) ) ) );

		$serial = new \stdClass();
		$serial->en = array(
			array(
				'language' => 'en',
				'value' => 'foo'
			),
			array(
				'language' => 'en',
				'value' => 'bar'
			)
		);

		$this->assertEquals( $serial, $serializer->serialize( $aliases ) );
	}

}
