<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Wikibase\Lib\Serializers\AliasSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;

/**
 * @covers Wikibase\Lib\Serializers\AliasSerializer
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 * @group WikibaseAliasSerializer
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class AliasSerializerTest extends \PHPUnit_Framework_TestCase {

	public function validProvider() {
		$validArgs = array();

		$options = new SerializationOptions();
		$options->setIndexTags( false );
		$aliases = array(
			"en" => array( "Roma", "Rome, Italy", "The Eternal City" ),
			"de" => array( "Die ewige Stadt", "" ),
			"it" => array( "Urbe", "Città eterna" ),
		);
		$expectedSerialization = array(
			"en" => array(
				array( "language" => "en", "value" => "Roma" ),
				array( "language" => "en", "value" => "Rome, Italy" ),
				array( "language" => "en", "value" => "The Eternal City" ),
			),
			"de" => array(
				array( "language" => "de", "value" => "Die ewige Stadt" ),
			),
			"it" => array(
				array( "language" => "it", "value" => "Urbe" ),
				array( "language" => "it", "value" => "Città eterna" ),
			),
		);
		$validArgs[] = array( $aliases, $options, $expectedSerialization );

		$options = new SerializationOptions();
		$options->setIndexTags( true );
		$aliases = array(
				"en" => array( "Roma", "Rome, Italy", "The Eternal City" ),
				"de" => array( "Die ewige Stadt", "" ),
				"it" => array( "Urbe", "Città eterna" ),
		);
		$expectedSerialization = array(
			array( "language" => "en", "value" => "Roma" ),
			array( "language" => "en", "value" => "Rome, Italy" ),
			array( "language" => "en", "value" => "The Eternal City" ),
			array( "language" => "de", "value" => "Die ewige Stadt" ),
			array( "language" => "it", "value" => "Urbe" ),
			array( "language" => "it", "value" => "Città eterna" ),
			"_element" => "alias",
		);
		$validArgs[] = array( $aliases, $options, $expectedSerialization );

		return $validArgs;
	}

	/**
	 * @dataProvider validProvider
	 */
	public function testGetSerialized( $aliases, $options, $expectedSerialization ) {
		$aliasSerializer = new AliasSerializer( $options );
		$serializedAliases = $aliasSerializer->getSerialized( $aliases );

		$this->assertEquals( $expectedSerialization, $serializedAliases );
	}

	public function invalidProvider() {
		$invalidArgs = array();

		$invalidArgs[] = array( 'foo' );
		$invalidArgs[] = array( 42 );

		return $invalidArgs;
	}

	/**
	 * @dataProvider invalidProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidGetSerialized( $aliases ) {
		$aliasSerializer = new AliasSerializer();
		$aliasSerializer->getSerialized( $aliases );
	}

	/**
	 * @dataProvider newFromSerializationProvider
	 */
	public function testNewFromSerialization( $expected, $serialized, $message ) {
		$aliasSerializer = new AliasSerializer( new SerializationOptions() );

		$deserializedAliases = $aliasSerializer->newFromSerialization( $serialized );
		$this->assertEquals( $expected, $deserializedAliases, $message );
	}

	public function newFromSerializationProvider() {
		$options = new SerializationOptions();
		$options->setIndexTags( true );
		$aliases = array(
			"en" => array( "Roma", "Rome, Italy", "The Eternal City" ),
			"de" => array( "Die ewige Stadt" ),
			"it" => array( "Urbe", "Città eterna" ),
		);

		$aliasSerializer = new AliasSerializer( $options );
		$serialized = $aliasSerializer->getSerialized( $aliases );

		$options->setIndexTags( false );

		$aliasSerializer = new AliasSerializer( $options );
		$serialized2 = $aliasSerializer->getSerialized( $aliases );

		$data = array();
		$data[] = array( $aliases, $serialized, 'serialization with index tags' );
		$data[] = array( $aliases, $serialized2, 'serialization without index tags' );

		return $data;
	}
}
