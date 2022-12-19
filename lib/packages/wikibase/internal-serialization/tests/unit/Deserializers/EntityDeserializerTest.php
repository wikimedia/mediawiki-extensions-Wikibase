<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\InternalSerialization\Deserializers\EntityDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\EntityDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDeserializerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp(): void {
		$this->deserializer = new EntityDeserializer(
			$this->getStubLegacyDeserializer(),
			$this->getStubCurrentDeserializer()
		);
	}

	/**
	 * @return DispatchableDeserializer
	 */
	private function getStubLegacyDeserializer() {
		$legacyDeserializer = $this->createMock( DispatchableDeserializer::class );

		$legacyDeserializer->expects( $this->any() )
			->method( 'isDeserializerFor' )
			->will( $this->returnCallback( static function( $serialization ) {
				return array_key_exists( 'entity', $serialization );
			} ) );

		$legacyDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnValue( 'legacy' ) );

		return $legacyDeserializer;
	}

	/**
	 * @return DispatchableDeserializer
	 */
	private function getStubCurrentDeserializer() {
		$currentDeserializer = $this->createMock( DispatchableDeserializer::class );

		$currentDeserializer->expects( $this->any() )
			->method( 'isDeserializerFor' )
			->will( $this->returnCallback( static function( $serialization ) {
				return array_key_exists( 'id', $serialization );
			} ) );

		$currentDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnValue( 'current' ) );

		return $currentDeserializer;
	}

	public function testGivenLegacySerialization_legacyIsDetected() {
		$returnValue = $this->deserializer->deserialize( [ 'entity' => [ 'item', 1 ] ] );
		$this->assertEquals( 'legacy', $returnValue );
	}

	public function testGivenCurrentSerialization_currentIsDetected() {
		$returnValue = $this->deserializer->deserialize( [ 'id' => 'Q1' ] );
		$this->assertEquals( 'current', $returnValue );
	}

	/**
	 * @return DispatchableDeserializer
	 */
	private function getThrowingDeserializer() {
		$currentDeserializer = $this->createMock( DispatchableDeserializer::class );

		$currentDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->throwException( new DeserializationException() ) );

		return $currentDeserializer;
	}

	public function testLegacySerializationWithoutId_exceptionIsThrown() {
		$deserializer = new EntityDeserializer(
			$this->getStubLegacyDeserializer(),
			$this->getThrowingDeserializer()
		);

		$this->expectException( 'Deserializers\Exceptions\DeserializationException' );
		$deserializer->deserialize( $this->getLegacyItemSerializationWithoutId() );
	}

	private function getLegacyItemSerializationWithoutId() {
		return [ 'aliases' => [
			'en' => [ 'foo', 'bar' ],
		] ];
	}

	public function testCurrentSerializationWithoutId_exceptionIsThrown() {
		$deserializer = new EntityDeserializer(
			$this->getThrowingDeserializer(),
			$this->getStubCurrentDeserializer()
		);

		$this->expectException( 'Deserializers\Exceptions\DeserializationException' );
		$deserializer->deserialize( $this->getCurrentItemSerializationWithoutId() );
	}

	private function getCurrentItemSerializationWithoutId() {
		return [ 'aliases' => [
			'en' => [
				[
					'language' => 'en',
					'value' => 'foo',
				],
				[
					'language' => 'en',
					'value' => 'bar',
				],
			],
		] ];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_exceptionIsThrown( $serialization ) {
		$deserializer = new EntityDeserializer(
			$this->getThrowingDeserializer(),
			$this->getThrowingDeserializer()
		);

		$this->expectException( DeserializationException::class );
		$deserializer->deserialize( $serialization );
	}

	public function invalidSerializationProvider() {
		return [
			[ null ],
			[ 5 ],
			[ [] ],
			[ [ 'entity' => 'P42', 'datatype' => null ] ],
		];
	}

}
