<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Normalization;

use DataValues\StringValue;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Normalization\DataValueNormalizer;
use Wikibase\Lib\Normalization\SnakNormalizer;
use Wikibase\Lib\StringNormalizer;
use Wikimedia\Assert\ParameterElementTypeException;

/**
 * @covers \Wikibase\Lib\Normalization\SnakNormalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SnakNormalizerTest extends TestCase {

	private const STRING_PROPERTY = 'P1';
	private const UNKNOWN_PROPERTY = 'P9';

	private function getSnakNormalizer( array $normalizerDefinitions ): SnakNormalizer {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( self::STRING_PROPERTY ), 'string' );

		return new SnakNormalizer(
			$dataTypeLookup,
			new NullLogger(),
			$normalizerDefinitions
		);
	}

	private function uppercasingNormalizer(): DataValueNormalizer {
		$normalizer = $this->createMock( DataValueNormalizer::class );
		$normalizer->method( 'normalize' )
			->willReturnCallback( static function ( StringValue $value ) {
				return new StringValue( strtoupper( $value->getValue() ) );
			} );
		return $normalizer;
	}

	private function repeatingNormalizer( int $repetitions ): DataValueNormalizer {
		$normalizer = $this->createMock( DataValueNormalizer::class );
		$normalizer->method( 'normalize' )
			->willReturnCallback( static function ( StringValue $value ) use ( $repetitions ) {
				return new StringValue( str_repeat( $value->getValue(), $repetitions ) );
			} );
		return $normalizer;
	}

	/** Return a function which will always return the given constant value. */
	private function constantCallable( $return ): callable {
		return static function () use ( $return ) {
			return $return;
		};
	}

	/** @dataProvider provideNonValueSnak */
	public function testNonValueSnak( Snak $snak ): void {
		$normalizer = $this->getSnakNormalizer( [] );

		$this->assertSame( $snak, $normalizer->normalize( $snak ) );
	}

	public function provideNonValueSnak(): iterable {
		$propertyId = new NumericPropertyId( self::STRING_PROPERTY );
		yield 'somevalue' => [ new PropertySomeValueSnak( $propertyId ) ];
		yield 'novalue' => [ new PropertyNoValueSnak( $propertyId ) ];
	}

	/** @dataProvider provideEmptyDefinitions */
	public function testEmptyDefinitions( array $definitions ): void {
		$value = new StringValue( '' );
		$snak = new PropertyValueSnak( new NumericPropertyId( self::STRING_PROPERTY ), $value );
		$normalizer = $this->getSnakNormalizer( $definitions );

		$normalizedSnak = $normalizer->normalize( $snak );

		$this->assertInstanceOf( PropertyValueSnak::class, $normalizedSnak );
		$this->assertSame( $snak->getPropertyId(), $normalizedSnak->getPropertyId() );
		$this->assertSame( $value, $normalizedSnak->getDataValue() );
	}

	public function provideEmptyDefinitions(): iterable {
		$returnEmpty = $this->constantCallable( [] );

		yield 'no definitions' => [ [] ];
		yield 'empty PT' => [ [ 'PT:string' => $returnEmpty ] ];
		yield 'empty VT' => [ [ 'VT:string' => $returnEmpty ] ];
		yield 'empty PT+VT' => [ [
			'PT:string' => $returnEmpty,
			'VT:string' => $returnEmpty,
		] ];
	}

	public function testUnrelatedDefinitions(): void {
		$wasCalled = false;
		$callable = static function () use ( &$wasCalled ) {
			$wasCalled = true;
			return [];
		};
		$normalizer = $this->getSnakNormalizer( [
			'PT:monolingualtext' => $callable,
			'VT:monolingualtext' => $callable,
		] );

		$propertyId = new NumericPropertyId( self::STRING_PROPERTY );
		$value = new StringValue( '' );
		$snak = new PropertyValueSnak( $propertyId, $value );
		$normalizer->normalize( $snak );

		$this->assertFalse( $wasCalled,
			'callable for unrelated types was never called' );
	}

	public function testOneValueTypeNormalizer(): void {
		$normalizer = $this->getSnakNormalizer( [
			'VT:string' => $this->constantCallable( $this->uppercasingNormalizer() ),
		] );

		$propertyId = new NumericPropertyId( self::STRING_PROPERTY );
		$value = new StringValue( 'abc' );
		$snak = new PropertyValueSnak( $propertyId, $value );
		/** @var PropertyValueSnak $normalizedSnak */
		$normalizedSnak = $normalizer->normalize( $snak );

		$this->assertSame( 'ABC', $normalizedSnak->getDataValue()->getValue() );
	}

	public function testTwoValueTypeNormalizers(): void {
		$normalizer = $this->getSnakNormalizer( [
			'VT:string' => $this->constantCallable( [
				$this->uppercasingNormalizer(),
				$this->repeatingNormalizer( 2 ),
			] ),
		] );

		$propertyId = new NumericPropertyId( self::STRING_PROPERTY );
		$value = new StringValue( 'abc' );
		$snak = new PropertyValueSnak( $propertyId, $value );
		/** @var PropertyValueSnak $normalizedSnak */
		$normalizedSnak = $normalizer->normalize( $snak );

		$this->assertSame( 'ABCABC', $normalizedSnak->getDataValue()->getValue() );
	}

	public function testOnePropertyTypeNormalizer(): void {
		$normalizer = $this->getSnakNormalizer( [
			'PT:string' => $this->constantCallable( $this->uppercasingNormalizer() ),
		] );

		$propertyId = new NumericPropertyId( self::STRING_PROPERTY );
		$value = new StringValue( 'abc' );
		$snak = new PropertyValueSnak( $propertyId, $value );
		/** @var PropertyValueSnak $normalizedSnak */
		$normalizedSnak = $normalizer->normalize( $snak );

		$this->assertSame( 'ABC', $normalizedSnak->getDataValue()->getValue() );
	}

	public function testTwoPropertyTypeNormalizers(): void {
		$normalizer = $this->getSnakNormalizer( [
			'PT:string' => $this->constantCallable( [
				$this->uppercasingNormalizer(),
				$this->repeatingNormalizer( 2 ),
			] ),
		] );

		$propertyId = new NumericPropertyId( self::STRING_PROPERTY );
		$value = new StringValue( 'abc' );
		$snak = new PropertyValueSnak( $propertyId, $value );
		/** @var PropertyValueSnak $normalizedSnak */
		$normalizedSnak = $normalizer->normalize( $snak );

		$this->assertSame( 'ABCABC', $normalizedSnak->getDataValue()->getValue() );
	}

	public function testTwoDataTypeOnePropertyTypeNormalizers(): void {
		$normalizer = $this->getSnakNormalizer( [
			'VT:string' => $this->constantCallable( [
				$this->uppercasingNormalizer(),
				$this->repeatingNormalizer( 2 ),
			] ),
			'PT:string' => $this->constantCallable( $this->repeatingNormalizer( 3 ) ),
		] );

		$propertyId = new NumericPropertyId( self::STRING_PROPERTY );
		$value = new StringValue( 'abc' );
		$snak = new PropertyValueSnak( $propertyId, $value );
		/** @var PropertyValueSnak $normalizedSnak */
		$normalizedSnak = $normalizer->normalize( $snak );

		$this->assertSame( 'ABCABCABCABCABCABC', $normalizedSnak->getDataValue()->getValue() );
	}

	public function testUnknownProperty(): void {
		$normalizer = $this->getSnakNormalizer( [
			'VT:string' => $this->constantCallable( $this->uppercasingNormalizer() ),
			'PT:string' => $this->constantCallable( $this->repeatingNormalizer( 2 ) ), // unused
		] );

		$propertyId = new NumericPropertyId( self::UNKNOWN_PROPERTY );
		$value = new StringValue( 'abc' );
		$snak = new PropertyValueSnak( $propertyId, $value );
		/** @var PropertyValueSnak $normalizedSnak */
		$normalizedSnak = $normalizer->normalize( $snak );

		$this->assertSame( 'ABC', $normalizedSnak->getDataValue()->getValue() );
	}

	public function testInvalidNormalizers(): void {
		$normalizer = $this->getSnakNormalizer( [
			'VT:string' => $this->constantCallable( new StringNormalizer() ),
		] );

		$propertyId = new NumericPropertyId( self::STRING_PROPERTY );
		$value = new StringValue( 'abc' );
		$snak = new PropertyValueSnak( $propertyId, $value );

		$this->expectException( ParameterElementTypeException::class );
		$this->expectExceptionMessage( 'VT:string' );
		$normalizer->normalize( $snak );
	}

}
