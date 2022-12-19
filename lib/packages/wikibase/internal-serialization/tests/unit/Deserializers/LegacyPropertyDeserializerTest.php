<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\InternalSerialization\Deserializers\LegacyEntityIdDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyFingerprintDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyPropertyDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacyPropertyDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyPropertyDeserializerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp(): void {
		$this->deserializer = new LegacyPropertyDeserializer(
			new LegacyEntityIdDeserializer( new BasicEntityIdParser() ),
			new LegacyFingerprintDeserializer()
		);
	}

	public function testGivenNonArraySerialization_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( null );
	}

	private function expectDeserializationException() {
		$this->expectException( DeserializationException::class );
	}

	public function testGivenNoDataType_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [] );
	}

	public function testGivenNonStringDataType_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [ 'datatype' => null ] );
	}

	public function testGivenValidDataType_dataTypeIsSet() {
		$property = $this->deserializer->deserialize( [ 'datatype' => 'foo' ] );
		$this->assertEquals( 'foo', $property->getDataTypeId() );
	}

	public function testGivenInvalidEntityId_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [
			'datatype' => 'foo',
			'entity' => 'spam spam spam',
		] );
	}

	public function testGivenNonPropertyEntityId_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [
			'datatype' => 'foo',
			'entity' => 'q42',
		] );
	}

	public function testGivenNoPropertyIdId_noPropertyIdIsSet() {
		$property = $this->deserializer->deserialize( [ 'datatype' => 'foo' ] );
		$this->assertNull( $property->getId() );
	}

	public function testGivenValidPropertyIdId_propertyIdIsSet() {
		$property = $this->deserializer->deserialize( [
			'datatype' => 'foo',
			'entity' => 'p42',
		] );

		$this->assertEquals( new NumericPropertyId( 'p42' ), $property->getId() );
	}

	/**
	 * @dataProvider TermListProvider
	 */
	public function testGivenLabels_getLabelsReturnsThem( array $labels ) {
		/** @var Property $property */
		$property = $this->deserializer->deserialize( [
			'datatype' => 'foo',
			'label' => $labels,
		] );

		$this->assertEquals( $labels, $property->getFingerprint()->getLabels()->toTextArray() );
	}

	public function TermListProvider() {
		return [
			[ [] ],

			[ [
				'en' => 'foo',
				'de' => 'bar',
			] ],
		];
	}

	public function testGivenInvalidLabels_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [ 'label' => null ] );
	}

	/**
	 * @dataProvider TermListProvider
	 */
	public function testGivenDescriptions_getDescriptionsReturnsThem( array $descriptions ) {
		/** @var Property $property */
		$property = $this->deserializer->deserialize( [
			'datatype' => 'foo',
			'description' => $descriptions,
		] );

		$this->assertEquals( $descriptions, $property->getFingerprint()->getDescriptions()->toTextArray() );
	}

	public function testGivenInvalidAliases_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [ 'aliases' => null ] );
	}

	/**
	 * @dataProvider aliasesListProvider
	 */
	public function testGivenAliases_getAliasesReturnsThem( array $aliases ) {
		/** @var Property $property */
		$property = $this->deserializer->deserialize( [
			'datatype' => 'foo',
			'aliases' => $aliases,
		] );

		$this->assertEquals( $aliases, $property->getFingerprint()->getAliasGroups()->toTextArray() );
	}

	public function aliasesListProvider() {
		return [
			[ [] ],

			[ [
				'en' => [ 'foo', 'bar' ],
				'de' => [ 'foo', 'bar', 'baz' ],
				'nl' => [ 'bah' ],
			] ],
		];
	}

}
