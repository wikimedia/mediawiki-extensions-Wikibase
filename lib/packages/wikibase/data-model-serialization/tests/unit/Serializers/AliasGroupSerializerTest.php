<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Serializers\AliasGroupSerializer;
use Wikibase\DataModel\Serializers\TermSerializer;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupFallback;

/**
 * @covers Wikibase\DataModel\Serializers\AliasGroupSerializer
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class AliasGroupSerializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( AliasGroup $input, $expected ) {
		$serializer = new AliasGroupSerializer();

		$output = $serializer->serialize( $input );

		$this->assertEquals( $expected, $output );
	}

	public function serializationProvider() {
		return array(
			array(
				new AliasGroup( 'en', array() ),
				array(),
			),
			array(
				new AliasGroup( 'en', array( 'One' ) ),
				array( 'en' => array(
					array( 'language' => 'en', 'value' => 'One' ),
				) ),
			),
			array(
				new AliasGroup( 'en', array( 'One', 'Pony' ) ),
				array( 'en' => array(
					array( 'language' => 'en', 'value' => 'One' ),
					array( 'language' => 'en', 'value' => 'Pony' ),
				) ),
			),
			array(
				new AliasGroupFallback( 'en', array( 'One', 'Pony' ), 'de', 'fr' ),
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
		$serializer->serialize( new \stdClass() );
	}

}
