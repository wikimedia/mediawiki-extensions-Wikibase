<?php

namespace Wikibase\Repo\Tests\Parsers;

use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use Wikibase\Repo\Parsers\MonolingualTextParser;

/**
 * @covers \Wikibase\Repo\Parsers\MonolingualTextParser
 *
 * @group ValueParsers
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class MonolingualTextParserTest extends \PHPUnit\Framework\TestCase {

	public function textProvider() {
		return [
			'empty' => [ 'en', '' ],
			'space' => [ 'en', ' ' ],
			'hello' => [ 'en', 'hello' ],
			'hallo' => [ 'de', 'hallo' ],
			'trim' => [ 'de ', 'hallo ' ],
		];
	}

	/**
	 * @dataProvider textProvider
	 * @param string $languageCode
	 * @param string $text
	 */
	public function testParse( $languageCode, $text ) {
		$options = new ParserOptions( [ 'valuelang' => $languageCode ] );
		$parser = new MonolingualTextParser( $options );
		$value = $parser->parse( $text );

		$this->assertEquals( trim( $text ), $value->getText() );
		$this->assertEquals( trim( $languageCode ), $value->getLanguageCode() );
	}

	public function testParse_missingLanguageOption() {
		$parser = new MonolingualTextParser();
		$this->expectException( ParseException::class );
		$parser->parse( 'Text' );
	}

	public function invalidLanguageCodeProvider() {
		return [
			[ null ],
			[ '' ],
		];
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testParse_invalidLanguageOption( $languageCode ) {
		$options = new ParserOptions( [ 'valuelang' => $languageCode ] );
		$parser = new MonolingualTextParser( $options );
		$this->expectException( ParseException::class );
		$parser->parse( 'Text' );
	}

	public function testParse_invalidText() {
		$options = new ParserOptions( [ 'valuelang' => 'en' ] );
		$parser = new MonolingualTextParser( $options );
		$this->expectException( ParseException::class );
		$parser->parse( null );
	}

}
