<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use ParserOptions;
use ParserOutput;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\ParserOutput\TermboxFlag;
use Wikibase\Repo\ParserOutput\TermboxVersionParserCacheValueRejector;

/**
 * @covers \Wikibase\Repo\ParserOutput\TermboxVersionParserCacheValueRejector
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxVersionParserCacheValueRejectorTest extends TestCase {

	/**
	 * @dataProvider allParserOutputAOptionsCombinationsProvider
	 *
	 */
	public function testGivenTermboxWillNotShow_shouldNeverInvalidateCache( $requestParserOptions, $valueFromCache ) {
		$flag = $this->createMock( TermboxFlag::class );
		$flag->method( 'shouldRenderTermbox' )
			->willReturn( false );

		$rejector = new TermboxVersionParserCacheValueRejector( $flag );

		$this->assertTrue( $rejector->keepCachedValue( $valueFromCache, $requestParserOptions ) );
	}

	public function testGivenTermboxShowingAndParserOutputMissingOption_invalidateCacheWhenOptionUsed() {
		$flag = $this->createMock( TermboxFlag::class );
		$flag->method( 'shouldRenderTermbox' )
			->willReturn( true );

		$requestParserOptions = $this->getMockRequestParserOptionsWithTermboxVersionOption();
		$valueFromCache = new ParserOutput();

		$rejector = new TermboxVersionParserCacheValueRejector( $flag );
		$this->assertFalse( $rejector->keepCachedValue( $valueFromCache, $requestParserOptions ) );
	}

	public function testGivenTermboxShowingAndParserOutputHasOption_cacheNotInvalidated() {
		$flag = $this->createMock( TermboxFlag::class );
		$flag->method( 'shouldRenderTermbox' )
			->willReturn( true );

		$requestParserOptions = $this->getMockRequestParserOptionsWithTermboxVersionOption();
		$valueFromCache = $this->getMockParserOutputWithTermboxVersionOption();

		$rejector = new TermboxVersionParserCacheValueRejector( $flag );
		$this->assertTrue( $rejector->keepCachedValue( $valueFromCache, $requestParserOptions ) );
	}

	private function getMockParserOutputWithTermboxVersionOption() {
		$pOutput = new ParserOutput();
		$pOutput->recordOption( 'termboxVersion' );
		return $pOutput;
	}

	private function getMockRequestParserOptionsWithTermboxVersionOption() {
		$pOpts = ParserOptions::newFromAnon();
		$pOpts->setOption( 'termboxVersion', 3 );
		return $pOpts;
	}

	public function allParserOutputAOptionsCombinationsProvider() {
		return [
			[
				ParserOptions::newFromAnon(),
				new ParserOutput(),
			],
			[
				$this->getMockRequestParserOptionsWithTermboxVersionOption(),
				new ParserOutput(),
			],
			[
				ParserOptions::newFromAnon(),
				$this->getMockParserOutputWithTermboxVersionOption(),
			],
			[
				$this->getMockRequestParserOptionsWithTermboxVersionOption(),
				$this->getMockParserOutputWithTermboxVersionOption(),
			],
		];
	}

}
