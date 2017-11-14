<?php

namespace Wikibase\Repo\Tests\Parsers;

use ValueParsers\ParserOptions;
use Wikibase\Repo\Parsers\MonolingualTextParser;

/**
 * @covers Wikibase\Repo\Parsers\MonolingualTextParser
 *
 * @group ValueParsers
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class MonolingualTextParserTest extends \PHPUnit_Framework_TestCase {

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

	/**
	 * @expectedException \ValueParsers\ParseException
	 */
	public function testParse_missingLanguageOption() {
		$parser = new MonolingualTextParser();
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
	 * @expectedException \ValueParsers\ParseException
	 */
	public function testParse_invalidLanguageOption( $languageCode ) {
		$options = new ParserOptions( [ 'valuelang' => $languageCode ] );
		$parser = new MonolingualTextParser( $options );
		$parser->parse( 'Text' );
	}

	/**
	 * @expectedException \ValueParsers\ParseException
	 */
	public function testParse_invalidText() {
		$options = new ParserOptions( [ 'valuelang' => 'en' ] );
		$parser = new MonolingualTextParser( $options );
		$parser->parse( null );
	}

}
