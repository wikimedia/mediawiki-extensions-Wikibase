<?php

namespace Wikibase\Client\Tests\Unit\Hooks;

use MediaWiki\MediaWikiServices;
use Parser;
use ParserOptions;
use ParserOutput;
use Wikibase\Client\Hooks\NoLangLinkHandler;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\WikibaseClient;

/**
 * @covers \Wikibase\Client\Hooks\NoLangLinkHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class NoLangLinkHandlerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var int[]
	 */
	private $excludeNamespaces;

	/**
	 * @var int[]
	 */
	private $namespacesToInclude;

	protected function setUp(): void {
		$settings = WikibaseClient::getSettings();

		$this->excludeNamespaces = $settings->getSetting( 'excludeNamespaces' );
		$this->namespacesToInclude = $settings->getSetting( 'namespaces' );

		$settings->setSetting( 'excludeNamespaces', [] );
		$settings->setSetting( 'namespaces', [] );
	}

	protected function tearDown(): void {
		$settings = WikibaseClient::getSettings();

		$settings->setSetting( 'excludeNamespaces', $this->excludeNamespaces );
		$settings->setSetting( 'namespaces', $this->namespacesToInclude );
	}

	public function testGetAppendNoExternalLangLinks() {
		$parserOutput = new ParserOutput();

		NoLangLinkHandler::appendNoExternalLangLinks( $parserOutput, [ 'xy' ] );
		$actual = NoLangLinkHandler::getNoExternalLangLinks( $parserOutput );
		$this->assertEqualsCanonicalizing( [ 'xy' ], $actual );

		NoLangLinkHandler::appendNoExternalLangLinks( $parserOutput, [ 'abc', 'xy', 'def' ] );
		$actual = NoLangLinkHandler::getNoExternalLangLinks( $parserOutput );

		$this->assertEqualsCanonicalizing( [ 'abc', 'def', 'xy' ], $actual );
	}

	public function testDoHandle() {
		$handler = new NoLangLinkHandler( new NamespaceChecker( [] ) );
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$parser->startExternalParse( null, ParserOptions::newFromAnon(), Parser::OT_HTML );

		$handler->doHandle( $parser, [ 'en', 'fr' ] );
		$actual = NoLangLinkHandler::getNoExternalLangLinks( $parser->getOutput() );
		$this->assertEqualsCanonicalizing( [ 'en', 'fr' ], $actual );

		$handler->doHandle( $parser, [ '*', 'zh', 'en' ] );
		$actual = NoLangLinkHandler::getNoExternalLangLinks( $parser->getOutput() );
		$this->assertEqualsCanonicalizing( [ 'en', 'fr', '*', 'zh' ], $actual );
	}

	public function testHandle() {
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$parser->startExternalParse( null, ParserOptions::newFromAnon(), Parser::OT_HTML );

		NoLangLinkHandler::handle( $parser, '*' );
		$actual = NoLangLinkHandler::getNoExternalLangLinks( $parser->getOutput() );

		$this->assertEqualsCanonicalizing( [ '*' ], $actual );
	}

}
