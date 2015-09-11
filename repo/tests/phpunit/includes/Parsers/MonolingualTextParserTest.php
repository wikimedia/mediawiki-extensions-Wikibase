<?php

namespace Wikibase\Parsers\Test;

use ValueParsers\ParserOptions;
use Wikibase\Parsers\MonolingualTextParser;

/**
 * @covers Wikibase\Parsers\MonolingualTextParser
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class MonolingualTextParserTest extends \PHPUnit_Framework_TestCase {

	public function textProvider() {
		return array(
			'empty' => array( 'en', '' ),
			'space' => array( 'en', ' ' ),
			'hello' => array( 'en', 'hello' ),
			'hallo' => array( 'de', 'hallo' ),
			'trim' => array( 'de ', 'hallo ' ),
		);
	}

	/**
	 * @dataProvider textProvider
	 * @param string $languageCode
	 * @param string $text
	 */
	public function testParse( $languageCode, $text ) {
		$options = new ParserOptions( array( 'valuelang' => $languageCode ) );
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
		return array(
			array( null ),
			array( '' ),
		);
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 * @expectedException \ValueParsers\ParseException
	 */
	public function testParse_invalidLanguageOption( $languageCode ) {
		$options = new ParserOptions( array( 'valuelang' => $languageCode ) );
		$parser = new MonolingualTextParser( $options );
		$parser->parse( 'Text' );
	}

	/**
	 * @expectedException \ValueParsers\ParseException
	 */
	public function testParse_invalidText() {
		$options = new ParserOptions( array( 'valuelang' => 'en' ) );
		$parser = new MonolingualTextParser( $options );
		$parser->parse( null );
	}

}
