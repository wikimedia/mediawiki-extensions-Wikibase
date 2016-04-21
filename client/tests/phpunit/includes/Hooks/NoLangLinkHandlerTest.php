<?php

namespace Wikibase\Client\Tests\Hooks;

use Parser;
use ParserOptions;
use ParserOutput;
use Wikibase\Client\WikibaseClient;
use Wikibase\NamespaceChecker;
use Wikibase\NoLangLinkHandler;

/**
 * @covers Wikibase\NoLangLinkHandler
 *
 * @group WikibaseClient
 * @group HookHandler
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class NoLangLinkHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var int[]
	 */
	private $excludeNamespaces;

	/**
	 * @var int[]
	 */
	private $namespacesToInclude;

	protected function setUp() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		$this->excludeNamespaces = $settings->getSetting( 'excludeNamespaces' );
		$this->namespacesToInclude = $settings->getSetting( 'namespaces' );

		$settings->setSetting( 'excludeNamespaces', [] );
		$settings->setSetting( 'namespaces', [] );
	}

	protected function tearDown() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		$settings->setSetting( 'excludeNamespaces', $this->excludeNamespaces );
		$settings->setSetting( 'namespaces', $this->namespacesToInclude );
	}

	public function testGetSetNoExternalLangLinks() {
		$pout = new ParserOutput();
		$list = array( 'xy', 'abc' );

		NoLangLinkHandler::setNoExternalLangLinks( $pout, $list );
		$actual = NoLangLinkHandler::getNoExternalLangLinks( $pout );

		$this->assertEquals( $list, $actual );
	}

	public function testDoHandle() {
		$handler = new NoLangLinkHandler( new NamespaceChecker( [] ) );
		$parser = new Parser();
		$parser->startExternalParse( null, new ParserOptions(), Parser::OT_HTML );

		$handler->doHandle( $parser, array( 'en', 'fr' ) );
		$actual = NoLangLinkHandler::getNoExternalLangLinks( $parser->getOutput() );
		$this->assertEquals( array( 'en', 'fr' ), $actual );

		$handler->doHandle( $parser, array( '*', 'zh' ) );
		$actual = NoLangLinkHandler::getNoExternalLangLinks( $parser->getOutput() );
		$this->assertEquals( array( 'en', 'fr', '*', 'zh' ), $actual );
	}

	public function testHandle() {
		$parser = new Parser();
		$parser->startExternalParse( null, new ParserOptions(), Parser::OT_HTML );

		NoLangLinkHandler::handle( $parser, '*' );
		$actual = NoLangLinkHandler::getNoExternalLangLinks( $parser->getOutput() );

		$this->assertEquals( array( '*' ), $actual );
	}

}
