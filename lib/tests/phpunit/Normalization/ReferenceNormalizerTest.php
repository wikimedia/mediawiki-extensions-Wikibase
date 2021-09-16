<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Normalization;

use DataValues\StringValue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Normalization\ReferenceNormalizer;
use Wikibase\Lib\Normalization\SnakNormalizer;

/**
 * @covers \Wikibase\Lib\Normalization\ReferenceNormalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReferenceNormalizerTest extends TestCase {

	public function testEmptyReference(): void {
		$snakNormalizer = $this->createMock( SnakNormalizer::class );
		$snakNormalizer->expects( $this->never() )
			->method( 'normalize' );
		$referenceNormalizer = new ReferenceNormalizer( $snakNormalizer );

		$reference = $referenceNormalizer->normalize( new Reference() );

		$this->assertTrue( $reference->isEmpty() );
	}

	public function testNonemptyReference(): void {
		$snakNormalizer = $this->createMock( SnakNormalizer::class );
		$snakNormalizer->method( 'normalize' )
			->willReturnCallback( static function ( PropertyValueSnak $snak ) {
				return new PropertyValueSnak(
					$snak->getPropertyId(),
					new StringValue( strtoupper( $snak->getDataValue()->getValue() ) )
				);
			} );
		$referenceNormalizer = new ReferenceNormalizer( $snakNormalizer );

		$p1 = new NumericPropertyId( 'P1' );
		$p2 = new NumericPropertyId( 'P2' );
		$reference = $referenceNormalizer->normalize( new Reference( [
			new PropertyValueSnak( $p1, new StringValue( 'abc' ) ),
			new PropertyValueSnak( $p1, new StringValue( 'def' ) ),
			new PropertyValueSnak( $p2, new StringValue( 'ghi' ) ),
		] ) );

		$snaks = $reference->getSnaks()->getArrayCopy();
		$this->assertCount( 3, $snaks );
		$this->assertSame( $p1, $snaks[0]->getPropertyId() );
		$this->assertSame( 'ABC', $snaks[0]->getDataValue()->getValue() );
		$this->assertSame( $p1, $snaks[1]->getPropertyId() );
		$this->assertSame( 'DEF', $snaks[1]->getDataValue()->getValue() );
		$this->assertSame( $p2, $snaks[2]->getPropertyId() );
		$this->assertSame( 'GHI', $snaks[2]->getDataValue()->getValue() );
	}

}
