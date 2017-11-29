<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use PHPUnit_Framework_TestCase;
use Wikibase\InternalSerialization\Deserializers\StatementDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\StatementDeserializer
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class StatementDeserializerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$legacyDeserializer = $this->getMock( DispatchableDeserializer::class );
		$currentDeserializer = $this->getMock( DispatchableDeserializer::class );
		$this->deserializer = new StatementDeserializer( $legacyDeserializer, $currentDeserializer );
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_exceptionIsThrown( $serialization ) {
		$this->setExpectedException( DeserializationException::class );
		$this->deserializer->deserialize( $serialization );
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),
			array( 5 ),
		);
	}

}
