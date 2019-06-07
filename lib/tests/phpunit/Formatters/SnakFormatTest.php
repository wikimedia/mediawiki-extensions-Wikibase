<?php

namespace Wikibase\Lib\Tests\Formatters;

use InvalidArgumentException;
use PHPUnit4And6Compat;
use ReflectionClass;
use Wikibase\Lib\Formatters\SnakFormat;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\SnakFormat
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class SnakFormatTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function fallbackFormatProvider() {
		yield [ SnakFormatter::FORMAT_HTML, SnakFormatter::FORMAT_HTML_VERBOSE ];
		yield [ SnakFormatter::FORMAT_WIKI, SnakFormatter::FORMAT_WIKI ];
	}

	/**
	 * @dataProvider fallbackFormatProvider
	 */
	public function testGetFallbackFormat( $expect, $format ) {
		$snakFormatHelper = new SnakFormat();
		$this->assertSame( $expect, $snakFormatHelper->getFallbackFormat( $format ) );
	}

	/**
	 * Make sure all SnakeFormatter::FORMAT_* constants are known/supported
	 */
	public function testGetFallbackFormat_complete() {
		$snakFormatHelper = new SnakFormat();
		$refSnakFormatter = new ReflectionClass( SnakFormatter::class );

		foreach ( $refSnakFormatter->getConstants() as $cname => $cvalue ) {
			if ( strpos( $cname, 'FORMAT_' ) !== 0 ) {
				continue;
			}
			$this->assertInternalType(
				'string',
				$snakFormatHelper->getFallbackFormat( $cvalue )
			);
		}
	}

	public function testGetFallbackFormat_invalidFormat() {
		$snakFormatHelper = new SnakFormat();
		$this->setExpectedException( InvalidArgumentException::class );
		$snakFormatHelper->getFallbackFormat( 'JSON-XML' );
	}

	public function fallbackChainProvider() {
		yield [
			[ SnakFormatter::FORMAT_HTML ],
			SnakFormatter::FORMAT_HTML
		];
		yield [
			[ SnakFormatter::FORMAT_HTML_VERBOSE, SnakFormatter::FORMAT_HTML ],
			SnakFormatter::FORMAT_HTML_VERBOSE
		];
		yield [
			[
				SnakFormatter::FORMAT_HTML_VERBOSE_PREVIEW,
				SnakFormatter::FORMAT_HTML_VERBOSE,
				SnakFormatter::FORMAT_HTML
			],
			SnakFormatter::FORMAT_HTML_VERBOSE_PREVIEW
		];
	}

	/**
	 * @dataProvider fallbackChainProvider
	 */
	public function testGetFallbackChain( $expect, $format ) {
		$snakFormatHelper = new SnakFormat();
		$this->assertEquals( $expect, $snakFormatHelper->getFallbackChain( $format ) );
	}

	public function baseFormatProvider() {
		yield [ SnakFormatter::FORMAT_HTML, SnakFormatter::FORMAT_HTML_VERBOSE ];
		yield [ SnakFormatter::FORMAT_WIKI, SnakFormatter::FORMAT_WIKI ];
	}

	/**
	 * @dataProvider baseFormatProvider
	 */
	public function testGetBaseFormat( $expect, $format ) {
		$snakFormatHelper = new SnakFormat();
		$this->assertSame( $expect, $snakFormatHelper->getBaseFormat( $format ) );
	}

	public function possibleFormatProvider() {
		yield [ true, SnakFormatter::FORMAT_HTML, SnakFormatter::FORMAT_HTML ];
		yield [ true, SnakFormatter::FORMAT_HTML, SnakFormatter::FORMAT_HTML_VERBOSE ];
		yield [ false, SnakFormatter::FORMAT_PLAIN, SnakFormatter::FORMAT_HTML ];
	}

	/**
	 * @dataProvider possibleFormatProvider
	 */
	public function testIsPossibleFormat( $expect, $availableFormat, $targetFormat ) {
		$snakFormatHelper = new SnakFormat();
		$this->assertSame( $expect, $snakFormatHelper->isPossibleFormat( $availableFormat, $targetFormat ) );
	}

}
