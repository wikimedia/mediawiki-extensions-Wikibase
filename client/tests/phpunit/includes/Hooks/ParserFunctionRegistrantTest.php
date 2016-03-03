<?php

namespace Wikibase\Client\Tests\Hooks;

use Parser;
use Wikibase\Client\Hooks\ParserFunctionRegistrant;

/**
 * @covers Wikibase\Client\Hooks\ParserFunctionRegistrant
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ParserFunctionRegistrantTest extends \PHPUnit_Framework_TestCase {

	public function testRegisterPropertyParserFunctions() {
		$parser = $this->newParser();

		$registrant = new ParserFunctionRegistrant( true );
		$registrant->register( $parser );

		$functionHooks = $parser->getFunctionHooks();

		$this->assertEquals( array( 'noexternallanglinks', 'property' ), $functionHooks );
	}

	public function testRegisterOnlyNoExternalLangLinksFuntion() {
		$parser = $this->newParser();

		$registrant = new ParserFunctionRegistrant( false );
		$registrant->register( $parser );

		$functionHooks = $parser->getFunctionHooks();

		$this->assertEquals( array( 'noexternallanglinks' ), $functionHooks );
	}

	private function newParser() {
		$parserConfig = array( 'class' => 'Parser' );
		return new Parser( $parserConfig );
	}

}
