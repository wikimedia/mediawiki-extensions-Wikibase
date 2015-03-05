<?php

namespace Wikibase\Lib\Parsers\Test;

use ValueParsers\Test\StringValueParserTest;
use Wikibase\Lib\Parsers\EraParser;

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
	 * @deprecated since 0.3, just use getInstance.
	 */
	protected function getParserClass() {
		throw new \LogicException( 'Should not be called, use getInstance' );
	}

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return EraParser
	 */
	protected function getInstance() {
		return new EraParser();
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
			array( '   -100', array( '-', '100' ) ),
			array( '100BC', array( '-', '100' ) ),
			array( '100 BC', array( '-', '100' ) ),
			array( '100 BCE', array( '-', '100' ) ),
			array( '100 AD', array( '+', '100' ) ),
			array( '100 A. D.', array( '+', '100' ) ),
			array( '   100   B.   C.   ', array( '-', '100' ) ),
			array( '   100   Common   Era   ', array( '+', '100' ) ),
			array( '100 CE', array( '+', '100' ) ),
			array( '100CE', array( '+', '100' ) ),
			array( '+100', array( '+', '100' ) ),
			array( '100 Common Era', array( '+', '100' ) ),
			array( '100Common Era', array( '+', '100' ) ),
			array( '100 Before Common Era', array( '-', '100' ) ),
			array( '1 July 2013 Before Common Era', array( '-', '1 July 2013' ) ),
			array( 'June 2013 Before Common Era', array( '-', 'June 2013' ) ),
			array( '10-10-10 Before Common Era', array( '-', '10-10-10' ) ),
			array( 'FooBefore Common Era', array( '-', 'Foo' ) ),
			array( 'Foo Before Common Era', array( '-', 'Foo' ) ),
			array( '-1 000 000', array( '-', '1 000 000' ) ),
			array( '1 000 000', array( '+', '1 000 000' ) ),
			array( '1 000 000 B.C.', array( '-', '1 000 000' ) ),
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
