<?php

namespace Wikibase\Client\Tests\Hooks;

use PHPUnit4And6Compat;
use PHPUnit\Framework\TestCase;
use UtfNormal\Utils;
use Wikibase\Client\Hooks\ShortDescHandler;

/**
 * @covers Wikibase\Client\Hooks\ShortDescHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class ShortDescHandlerTest extends TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @var ShortDescHandler
	 */
	private $handler;

	public function setUp() {
		parent::setUp();
		$this->handler = new ShortDescHandler();
	}

	/**
	 * @dataProvider provideIsValid
	 * @param string $shortDesc
	 * @param bool $isValid
	 */
	public function testIsValid( $shortDesc, $isValid ) {
		$this->assertSame( $isValid, $this->handler->isValid( $shortDesc ) );
	}

	public function provideIsValid() {
		return [
			// sanitized parser function parameter, is valid
			'empty' => [ '', false ],
			'punctuation (period)' => [ '.', false ],
			'punctuation (middle dot)' => [ '·', false ],
			'whitespace (space)' => [ ' ', false ],
			'whitespace (tab)' => [ "\t", false ],
			'whitespace (non-breaking)' => [ Utils::codepointToUtf8( 0x00A0 ), false ],
			'combination' => [ '. .', false ],
			'valid' => [ ' foo ', true ],
		];
	}

	/**
	 * @dataProvider provideSanitize
	 * @param string $raw
	 * @param string $sanitized
	 */
	public function testSanitize( $raw, $sanitized ) {
		$this->assertSame( $sanitized, $this->handler->sanitize( $raw ) );
	}

	public function provideSanitize() {
		return [
			// expanded parser function parameter, sanitized value
			'trim' => [ ' foo ', 'foo' ],
			'remove HTML' => [ 'a<i>b</i>c', 'abc' ],
			'remove newline' => [ "a\nb\n \nc", 'a b c' ],
			'decode' => [ 'a&lt;div&gt;b', 'a<div>b' ],
			'decode and trim' => [ '&#32;a', 'a' ],
			'decode and remove newline' => [ 'a&#10;b', 'a b' ],
			'decode, remove newline and trim' => [ '&#10;b', 'b' ],
			'do not decode twice' => [ 'a&amp;lt;b', 'a&lt;b' ],
			'do not remove HTML after decode' => [ '&lt;b>a&lt;/b>', '<b>a</b>' ],
		];
	}

	/**
	 * @dataProvider provideDoHandle
	 * @param string $inputString
	 * @param string|null $pageProperty
	 */
	public function testDoHandle( $inputString, $pageProperty ) {
		$output = $this->getMockBuilder( \OutputPage::class )
			->disableOriginalConstructor()
			->getMock();
		$parser = $this->getMockBuilder( \Parser::class )
			->disableOriginalConstructor()
			->getMock();
		$parser->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $output );
		if ( $pageProperty === null ) {
			$output->expects( $this->never() )
				->method( 'setProperty' );
		} else {
			$output->expects( $this->once() )
				->method( 'setProperty' )
				->with( 'wikibase-shortdesc', $pageProperty );
		}
		$this->handler->doHandle( $parser, $inputString );
	}

	public function provideDoHandle() {
		return [
			// expanded parser function parameter, expected page property
			'invalid' => [ '', null ],
			'invalid #2' => [ ' ', null ],
			'invalid #3' => [ '&nbsp;', null ],
			'valid' => [ 'foo', 'foo' ],
			'valid #2' => [ ' <span> &lt;div&gt; ', '<div>' ],
		];
	}

}
