<?php

namespace Wikibase\Client\Tests\Hooks;

use Parser;
use ParserOptions;
use ParserOutput;
use Wikibase\NamespaceChecker;
use Wikibase\NoLangLinkHandler;

/**
 * @covers Wikibase\NoLangLinkHandler
 *
 * @group WikibaseClient
 * @group HookHandler
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class NoLangLinkHandlerTest extends \PHPUnit_Framework_TestCase {

	public function testGetSetNoExternalLangLinks() {
		$pout = new ParserOutput();
		$list = array( 'xy', 'abc' );

		NoLangLinkHandler::setNoExternalLangLinks( $pout, $list );
		$actual = NoLangLinkHandler::getNoExternalLangLinks( $pout );

		$this->assertEquals( $list, $actual );
	}

	public function testDoHandle() {
		$handler = new NoLangLinkHandler( new NamespaceChecker( array(), array() ) );
		$parser = new Parser();
		$parser->startExternalParse( null, new ParserOptions(), Parser::OT_HTML );

		$handler->doHandle( $parser, 'en', 'fr' );
		$actual = NoLangLinkHandler::getNoExternalLangLinks( $parser->getOutput() );
		$this->assertEquals( array( 'en', 'fr' ), $actual );

		$handler->doHandle( $parser, '*', 'zh' );
		$actual = NoLangLinkHandler::getNoExternalLangLinks( $parser->getOutput() );
		$this->assertEquals( array( 'en', 'fr', '*', 'zh' ), $actual );
	}

}
