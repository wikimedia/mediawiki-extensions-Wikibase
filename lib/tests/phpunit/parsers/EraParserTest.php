<?php

namespace Wikibase\Lib\Parsers\Test;

use ValueParsers\Test\StringValueParserTest;

/**
 * @covers Wikibase\Lib\Parsers\EraParser
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 * @group TimeParsers
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class EraParserTest extends StringValueParserTest {

	/**
	 * @return string
	 */
	protected function getParserClass() {
		return 'Wikibase\Lib\Parsers\EraParser';
	}

	/**
	 * @return bool
	 */
	protected function requireDataValue() {
		return false;
	}

	public function validInputProvider() {
		return array(
			array( '+100', array( '+', '100' ) ),
			array( '-100', array( '-', '100' ) ),
			array( '100BC', array( '-', '100' ) ),
			array( '100 BC', array( '-', '100' ) ),
			array( '100 BCE', array( '-', '100' ) ),
			array( '100 AD', array( '+', '100' ) ),
			array( '100 CE', array( '+', '100' ) ),
			array( '100CE', array( '+', '100' ) ),
			array( '+100', array( '+', '100' ) ),
			array( '100 Common Era', array( '+', '100' ) ),
			array( '100Common Era', array( '+', '100' ) ),
			array( '100 Before Common Era', array( '-', '100' ) ),
		);
	}

	public function invalidInputProvider() {
		return array(
			array( '-100BC' ),
			array( '-100AD' ),
			array( '-100CE' ),
			array( '+100BC' ),
			array( '+100AD' ),
			array( '+100CE' ),
			array( '+100 Before Common Era' ),
			array( '+100 Common Era' ),
		);
	}
}