<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Normalization;

use DataValues\UnknownValue;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Normalization\CompositeDataValueNormalizer;
use Wikibase\Lib\Normalization\DataValueNormalizer;

/**
 * @covers \Wikibase\Lib\Normalization\CompositeDataValueNormalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CompositeDataValueNormalizerTest extends TestCase {

	public function testEmpty(): void {
		$value = new UnknownValue( null );
		$normalizer = new CompositeDataValueNormalizer( [] );

		$this->assertSame( $value, $normalizer->normalize( $value ) );
	}

	public function testThreeNormalizers(): void {
		$value0 = new UnknownValue( null );
		$value1 = new UnknownValue( null );
		$value2 = new UnknownValue( null );
		$value3 = new UnknownValue( null );

		$normalizer1 = $this->createMock( DataValueNormalizer::class );
		$normalizer1->expects( $this->once() )
			->method( 'normalize' )
			->with( $value0 )
			->willReturn( $value1 );
		$normalizer2 = $this->createMock( DataValueNormalizer::class );
		$normalizer2->expects( $this->once() )
			->method( 'normalize' )
			->with( $value1 )
			->willReturn( $value2 );
		$normalizer3 = $this->createMock( DataValueNormalizer::class );
		$normalizer3->expects( $this->once() )
			->method( 'normalize' )
			->with( $value2 )
			->willReturn( $value3 );

		$normalizer = new CompositeDataValueNormalizer( [
			$normalizer1,
			$normalizer2,
			$normalizer3,
		] );

		$this->assertSame( $value3, $normalizer->normalize( $value0 ) );
	}

}
