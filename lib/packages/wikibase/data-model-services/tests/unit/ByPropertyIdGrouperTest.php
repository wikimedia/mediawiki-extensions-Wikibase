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
	public function testConstructor( $argument ) {
		$instance = new ByPropertyIdGrouper( $argument );
		$this->assertCount( count( $argument ), $instance->getPropertyIds() );
	}

	public function validConstructorArgumentProvider() {
		return [
			[ [] ],
			[ [ $this->getPropertyIdProviderMock( 'P1' ) ] ],
			[ new ArrayObject() ],
			[ new ArrayObject( [ $this->getPropertyIdProviderMock( 'P1' ) ] ) ],
		];
	}

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testConstructorThrowsException( $argument ) {
		$this->expectException( InvalidArgumentException::class );
		new ByPropertyIdGrouper( $argument );
	}

	public function invalidConstructorArgumentProvider() {
		return [
			[ null ],
			[ 'notAnObject' ],
			[ [ null ] ],
			[ [ 'notAnObject' ] ],
			[ new ArrayObject( [ null ] ) ],
			[ new ArrayObject( [ 'notAnObject' ] ) ],
		];
	}

	public function provideGetPropertyIds() {
		$cases = [];

		$cases['empty list'] = [
			[],
			[],
		];

		$cases['some property ids'] = [
			[
				$this->getPropertyIdProviderMock( 'P42' ),
				$this->getPropertyIdProviderMock( 'P23' ),
			],
			[
				new NumericPropertyId( 'P42' ),
				new NumericPropertyId( 'P23' ),
			],
		];

		$cases['duplicate property ids'] = [
			$this->getPropertyIdProviders(),
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
	 * @param PropertyIdProvider[] $propertyIdProviders
	 * @param NumericPropertyId[] $expectedPropertyIds
	 */
	public function testGetPropertyIds( array $propertyIdProviders, array $expectedPropertyIds ) {
		$byPropertyIdGrouper = new ByPropertyIdGrouper( $propertyIdProviders );
		$propertyIds = $byPropertyIdGrouper->getPropertyIds();
		$this->assertEquals( $expectedPropertyIds, $propertyIds );
	}

	public function provideGetByPropertyId() {
		$cases = [];

		$cases[] = [
			$this->getPropertyIdProviders(),
			'P42',
			[ 'abc', 'jkl' ],
		];

		$cases[] = [
			$this->getPropertyIdProviders(),
			'P23',
			[ 'def' ],
		];

		return $cases;
	}

	/**
	 * @dataProvider provideGetByPropertyId
	 */
	public function testGetByPropertyId( array $propertyIdProviders, $propertyId, array $expectedValues ) {
		$byPropertyIdGrouper = new ByPropertyIdGrouper( $propertyIdProviders );
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

	public function provideHasPropertyId() {
		$cases = [];

		$cases[] = [ $this->getPropertyIdProviders(), 'P42', true ];
		$cases[] = [ $this->getPropertyIdProviders(), 'P23', true ];
		$cases[] = [ $this->getPropertyIdProviders(), 'P15', true ];
		$cases[] = [ $this->getPropertyIdProviders(), 'P10', true ];
		$cases[] = [ $this->getPropertyIdProviders(), 'P11', false ];

		return $cases;
	}

	/**
	 * @dataProvider provideHasPropertyId
	 */
	public function testHasPropertyId( array $propertyIdProviders, $propertyId, $expectedValue ) {
		$byPropertyIdGrouper = new ByPropertyIdGrouper( $propertyIdProviders );
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
			->will( $this->returnValue( new NumericPropertyId( $propertyId ) ) );

		$propertyIdProvider->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( $type ) );

		return $propertyIdProvider;
	}

}
