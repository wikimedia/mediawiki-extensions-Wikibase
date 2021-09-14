<?php

namespace Wikibase\Lib\Tests\Formatters\Reference;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\Lib\Formatters\Reference\ByCertainPropertyIdGrouper;

/**
 * @covers \Wikibase\Lib\Formatters\Reference\ByCertainPropertyIdGrouper
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ByCertainPropertyIdGrouperTest extends TestCase {

	public function testGrouping() {
		$p1 = new NumericPropertyId( 'P1' );
		$p2 = new NumericPropertyId( 'P2' );
		$p3 = new NumericPropertyId( 'P3' );
		$p4 = new NumericPropertyId( 'P4' );
		$snak11 = new PropertyNoValueSnak( $p1 );
		$snak12 = new PropertySomeValueSnak( $p1 );
		$snak21 = new PropertyNoValueSnak( $p2 );
		$snak22 = new PropertySomeValueSnak( $p2 );
		$snak31 = new PropertyNoValueSnak( $p3 );
		$snak32 = new PropertySomeValueSnak( $p3 );
		$snak41 = new PropertyNoValueSnak( $p4 );
		$snak42 = new PropertySomeValueSnak( $p4 );

		$snaks = new ByCertainPropertyIdGrouper(
			[ $snak11, $snak21, $snak12, $snak22, $snak41, $snak31, $snak32, $snak42 ],
			[ $p1, $p2 ]
		);

		$this->assertSame( [ $snak11, $snak12 ], $snaks->getByPropertyId( $p1 ) );
		$this->assertSame( [ $snak21, $snak22 ], $snaks->getByPropertyId( $p2 ) );
		$this->assertSame( [ $snak41, $snak31, $snak32, $snak42 ], $snaks->getOthers() );
	}

	/** @dataProvider provideKnownAndUnknownPropertyId */
	public function testGetByProperty_certainPropertyWithoutSnaks( ?NumericPropertyId $propertyId ) {
		$snaks = new ByCertainPropertyIdGrouper( [], [ $propertyId ] );

		$this->assertSame( [], $snaks->getByPropertyId( $propertyId ) );
	}

	/** @dataProvider provideKnownAndUnknownPropertyId */
	public function testGetByProperty_notCertainProperty( ?NumericPropertyId $propertyId ) {
		$snaks = new ByCertainPropertyIdGrouper( [], [] );

		$this->expectException( InvalidArgumentException::class );
		$snaks->getByPropertyId( $propertyId );
	}

	public function provideKnownAndUnknownPropertyId(): iterable {
		yield 'known' => [ new NumericPropertyId( 'P1' ) ];
		yield 'unknown' => [ null ];
	}

}
