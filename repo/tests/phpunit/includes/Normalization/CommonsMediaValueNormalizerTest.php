<?php

namespace Wikibase\Repo\Tests\Normalization;

use DataValues\BooleanValue;
use DataValues\StringValue;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Wikibase\Repo\CachingCommonsMediaFileNameLookup;
use Wikibase\Repo\Normalization\CommonsMediaValueNormalizer;

/**
 * @covers \Wikibase\Repo\Normalization\CommonsMediaValueNormalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CommonsMediaValueNormalizerTest extends TestCase {

	private function getCommonsMediaValueNormalizer(
		CachingCommonsMediaFileNameLookup $fileNameLookup
	): CommonsMediaValueNormalizer {
		return new CommonsMediaValueNormalizer(
			$fileNameLookup,
			new NullLogger()
		);
	}

	public function testNormalize(): void {
		$fileNameLookup = $this->createMock( CachingCommonsMediaFileNameLookup::class );
		$fileNameLookup->expects( $this->once() )
			->method( 'lookupFileName' )
			->with( 'Test_file.jpg' )
			->willReturn( 'Test file.jpg' );
		$normalizer = $this->getCommonsMediaValueNormalizer( $fileNameLookup );
		$value = new StringValue( 'Test_file.jpg' );

		$normalized = $normalizer->normalize( $value );

		$this->assertInstanceOf( StringValue::class, $normalized );
		$this->assertSame( 'Test file.jpg', $normalized->getValue() );
	}

	public function testNormalize_missing(): void {
		$fileNameLookup = $this->createMock( CachingCommonsMediaFileNameLookup::class );
		$fileNameLookup->expects( $this->once() )
			->method( 'lookupFileName' )
			->with( 'Missing file.jpg' )
			->willReturn( null );
		$normalizer = $this->getCommonsMediaValueNormalizer( $fileNameLookup );
		$value = new StringValue( 'Missing file.jpg' );

		$normalized = $normalizer->normalize( $value );

		$this->assertInstanceOf( StringValue::class, $normalized );
		$this->assertSame( 'Missing file.jpg', $normalized->getValue() );
	}

	public function testNormalize_badType(): void {
		$fileNameLookup = $this->createMock( CachingCommonsMediaFileNameLookup::class );
		$fileNameLookup->expects( $this->never() )
			->method( 'lookupFileName' );
		$normalizer = $this->getCommonsMediaValueNormalizer( $fileNameLookup );
		$value = new BooleanValue( true );

		$normalized = $normalizer->normalize( $value );

		$this->assertSame( $value, $normalized );
	}

}
