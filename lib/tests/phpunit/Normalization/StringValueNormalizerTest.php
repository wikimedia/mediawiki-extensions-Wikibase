<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Normalization;

use DataValues\StringValue;
use DataValues\UnknownValue;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Wikibase\Lib\Normalization\StringValueNormalizer;
use Wikibase\Lib\StringNormalizer;

/**
 * @covers \Wikibase\Lib\Normalization\StringValueNormalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StringValueNormalizerTest extends TestCase {

	public function getStringValueNormalizer(
		StringNormalizer $stringNormalizer
	): StringValueNormalizer {
		return new StringValueNormalizer(
			$stringNormalizer,
			new NullLogger()
		);
	}

	public function testNormalize_mockStringNormalizer(): void {
		$stringNormalizer = $this->createMock( StringNormalizer::class );
		$stringNormalizer->expects( $this->once() )
			->method( 'cleanupToNFC' )
			->with( "\u{0061}\u{0301}" )
			->willReturn( "\u{00E1}" );
		$normalizer = $this->getStringValueNormalizer( $stringNormalizer );
		$value = new StringValue( "\u{0061}\u{0301}" );

		$normalized = $normalizer->normalize( $value );

		$this->assertInstanceOf( StringValue::class, $normalized );
		$this->assertSame( "\u{00E1}", $normalized->getValue() );
	}

	public function testNormalize_realStringNormalizer(): void {
		$normalizer = $this->getStringValueNormalizer( new StringNormalizer() );
		$value = new StringValue( "\u{0061}\u{0301}" );

		$normalized = $normalizer->normalize( $value );

		$this->assertInstanceOf( StringValue::class, $normalized );
		$this->assertSame( "\u{00E1}", $normalized->getValue() );
	}

	public function testNormalize_badType(): void {
		$stringNormalizer = $this->createMock( StringNormalizer::class );
		$stringNormalizer->expects( $this->never() )
			->method( 'cleanupToNFC' );
		$normalizer = $this->getStringValueNormalizer( $stringNormalizer );
		$value = new UnknownValue( null );

		$normalized = $normalizer->normalize( $value );

		$this->assertSame( $value, $normalized );
	}

}
