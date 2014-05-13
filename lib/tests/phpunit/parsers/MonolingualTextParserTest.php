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
 */
class MonolingualTextParserTest extends \PHPUnit_Framework_TestCase {

	public function textProvider() {
		return array(
			'empty' => array( 'en', '' ),
			'hello' => array( 'en', 'hello' ),
			'hallo' => array( 'de', 'hallo' ),
		);
	}

	/**
	 * @dataProvider textProvider
	 *
	 * @param $lang
	 * @param $text
	 */
	public function testParse( $lang, $text ) {
		$options = new ParserOptions( array( 'lang' => $lang ) );
		$parser = new MonolingualTextParser( $options );
		$value = $parser->parse( $text );

		$this->assertEquals( $text, $value->getText() );
		$this->assertEquals( $lang, $value->getLanguageCode() );
	}

}
