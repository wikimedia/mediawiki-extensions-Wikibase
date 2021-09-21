<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\InternalSerialization\Deserializers\StatementDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\StatementDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class StatementDeserializerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp(): void {
		$legacyDeserializer = $this->createMock( DispatchableDeserializer::class );
		$currentDeserializer = $this->createMock( DispatchableDeserializer::class );
		$this->deserializer = new StatementDeserializer( $legacyDeserializer, $currentDeserializer );
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_exceptionIsThrown( $serialization ) {
		$this->expectException( DeserializationException::class );
		$this->deserializer->deserialize( $serialization );
	}

	public function invalidSerializationProvider() {
		return [
			[ null ],
			[ 5 ],
		];
	}

}
