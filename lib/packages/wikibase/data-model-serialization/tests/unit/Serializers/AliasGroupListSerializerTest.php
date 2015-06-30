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
	public function testSerialization( AliasGroupList $input, $expected ) {
		$serializer = new AliasGroupListSerializer( false );

		$output = $serializer->serialize( $input );

		$this->assertEquals( $expected, $output );
	}

	public function serializationProvider() {
		return array(
			array(
				new AliasGroupList( array( new AliasGroup( 'en', array() ) ) ),
				array(),
			),
			array(
				new AliasGroupList( array( new AliasGroup( 'en', array( 'One' ) ) ) ),
				array( 'en' => array(
					array( 'language' => 'en', 'value' => 'One' ),
				) ),
			),
			array(
				new AliasGroupList( array( new AliasGroup( 'en', array( 'One', 'Pony' ) ) ) ),
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

}
