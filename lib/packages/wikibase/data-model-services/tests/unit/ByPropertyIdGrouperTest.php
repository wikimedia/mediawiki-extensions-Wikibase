<?php

namespace Wikibase\DataModel\Services\Tests;

use ArrayObject;
use InvalidArgumentException;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\PropertyIdProvider;
use Wikibase\DataModel\Services\ByPropertyIdGrouper;
use Wikibase\DataModel\Snak\Snak;

/**
 * @covers \Wikibase\DataModel\Services\ByPropertyIdGrouper
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Kreuz
 */
class ByPropertyIdGrouperTest extends TestCase {

	/**
	 * @dataProvider validConstructorArgumentProvider
	 */
	public function testConstructor( callable $argumentFactory ) {
		$argument = $argumentFactory( $this );
		$instance = new ByPropertyIdGrouper( $argument );
		$this->assertSameSize( $argument, $instance->getPropertyIds() );
	}

	public static function validConstructorArgumentProvider() {
		return [
			[ fn () => [] ],
			[ fn ( self $self ) => [ $self->getPropertyIdProviderMock( 'P1' ) ] ],
			[ fn () => new ArrayObject() ],
			[ fn ( self $self ) => new ArrayObject( [ $self->getPropertyIdProviderMock( 'P1' ) ] ) ],
		];
	}

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testConstructorThrowsException( $argument ) {
		$this->expectException( InvalidArgumentException::class );
		new ByPropertyIdGrouper( $argument );
	}

	public static function invalidConstructorArgumentProvider() {
		return [
			[ null ],
			[ 'notAnObject' ],
			[ [ null ] ],
			[ [ 'notAnObject' ] ],
			[ new ArrayObject( [ null ] ) ],
			[ new ArrayObject( [ 'notAnObject' ] ) ],
		];
	}

	public static function provideGetPropertyIds() {
		$cases = [];

		$cases['empty list'] = [
			fn () => [],
			[],
		];

		$cases['some property ids'] = [
			fn ( self $self ) => [
				$self->getPropertyIdProviderMock( 'P42' ),
				$self->getPropertyIdProviderMock( 'P23' ),
			],
			[
				new NumericPropertyId( 'P42' ),
				new NumericPropertyId( 'P23' ),
			],
		];

		$cases['duplicate property ids'] = [
			fn ( self $self ) => $self->getPropertyIdProviders(),
			[
				new NumericPropertyId( 'P42' ),
				new NumericPropertyId( 'P23' ),
				new NumericPropertyId( 'P15' ),
				new NumericPropertyId( 'P10' ),
			],
		];

		return $cases;
	}

	/**
	 * @dataProvider provideGetPropertyIds
	 * @param callable $propertyIdProvidersFactory
	 * @param NumericPropertyId[] $expectedPropertyIds
	 */
	public function testGetPropertyIds( callable $propertyIdProvidersFactory, array $expectedPropertyIds ) {
		$byPropertyIdGrouper = new ByPropertyIdGrouper( $propertyIdProvidersFactory( $this ) );
		$propertyIds = $byPropertyIdGrouper->getPropertyIds();
		$this->assertEquals( $expectedPropertyIds, $propertyIds );
	}

	public static function provideGetByPropertyId() {
		$cases = [];

		$cases[] = [
			fn ( self $self ) => $self->getPropertyIdProviders(),
			'P42',
			[ 'abc', 'jkl' ],
		];

		$cases[] = [
			fn ( self $self ) => $self->getPropertyIdProviders(),
			'P23',
			[ 'def' ],
		];

		return $cases;
	}

	/**
	 * @dataProvider provideGetByPropertyId
	 */
	public function testGetByPropertyId( callable $propertyIdProvidersFactory, $propertyId, array $expectedValues ) {
		$byPropertyIdGrouper = new ByPropertyIdGrouper( $propertyIdProvidersFactory( $this ) );
		$values = $byPropertyIdGrouper->getByPropertyId( new NumericPropertyId( $propertyId ) );
		array_walk( $values, static function( Snak &$value ) {
			$value = $value->getType();
		} );
		$this->assertEquals( $expectedValues, $values );
	}

	public function testGetByPropertyIdThrowsException() {
		$byPropertyIdGrouper = new ByPropertyIdGrouper( $this->getPropertyIdProviders() );
		$this->expectException( OutOfBoundsException::class );
		$byPropertyIdGrouper->getByPropertyId( new NumericPropertyId( 'P11' ) );
	}

	public static function provideHasPropertyId() {
		$cases = [];

		$cases[] = [ fn ( self $self ) => $self->getPropertyIdProviders(), 'P42', true ];
		$cases[] = [ fn ( self $self ) => $self->getPropertyIdProviders(), 'P23', true ];
		$cases[] = [ fn ( self $self ) => $self->getPropertyIdProviders(), 'P15', true ];
		$cases[] = [ fn ( self $self ) => $self->getPropertyIdProviders(), 'P10', true ];
		$cases[] = [ fn ( self $self ) => $self->getPropertyIdProviders(), 'P11', false ];

		return $cases;
	}

	/**
	 * @dataProvider provideHasPropertyId
	 */
	public function testHasPropertyId( callable $propertyIdProvidersFactory, $propertyId, $expectedValue ) {
		$byPropertyIdGrouper = new ByPropertyIdGrouper( $propertyIdProvidersFactory( $this ) );
		$this->assertEquals( $expectedValue, $byPropertyIdGrouper->hasPropertyId( new NumericPropertyId( $propertyId ) ) );
	}

	/**
	 * @return PropertyIdProvider[]
	 */
	private function getPropertyIdProviders() {
		return [
			$this->getPropertyIdProviderMock( 'P42', 'abc' ),
			$this->getPropertyIdProviderMock( 'P23', 'def' ),
			$this->getPropertyIdProviderMock( 'P15', 'ghi' ),
			$this->getPropertyIdProviderMock( 'P42', 'jkl' ),
			$this->getPropertyIdProviderMock( 'P10', 'mno' ),
		];
	}

	/**
	 * Creates a PropertyIdProvider mock which can return a value.
	 *
	 * @param string $propertyId
	 * @param string|null $type
	 *
	 * @return PropertyIdProvider
	 */
	private function getPropertyIdProviderMock( $propertyId, $type = null ) {
		$propertyIdProvider = $this->createMock( Snak::class );

		$propertyIdProvider->expects( $this->once() )
			->method( 'getPropertyId' )
			->willReturn( new NumericPropertyId( $propertyId ) );

		$propertyIdProvider->expects( $this->any() )
			->method( 'getType' )
			->willReturn( $type );

		return $propertyIdProvider;
	}

}
