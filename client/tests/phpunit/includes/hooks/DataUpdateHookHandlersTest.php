<?php

namespace Wikibase\Client\Test\Hooks;

use Parser;
use ParserOptions;
use ParserOutput;
use StripState;
use Title;
use Wikibase\Client\Hooks\DataUpdateHookHandlers;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\NamespaceChecker;
use Wikibase\Settings;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Client\Hooks\DataUpdateHookHandlers
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DataUpdateHookHandlersTest extends \MediaWikiTestCase {

	/**
	 * @param array $settings
	 *
	 * @return Settings
	 */
	private function newSettings( array $settings ) {
		$defaults = array(
			'namespaces' => array( NS_MAIN, NS_CATEGORY ),
			'siteGlobalid' => 'enwiki',
		);

		return new SettingsArray( array_merge( $defaults, $settings ) );
	}

	/**
	 * @return UsageUpdater
	 */
	private function getUsageUpdater() {
		$usageUpdater = $this->getMockBuilder( 'Wikibase\Client\Store\UsageUpdater' )
			->disableOriginalConstructor()
			->getMock();

		return $usageUpdater;
	}

	private function newDataUpdateHookHandlers( array $settings = array() ) {
		$settings = $this->newSettings( $settings );

		$namespaces = $settings->getSetting( 'namespaces' );
		$namespaceChecker = new NamespaceChecker( array(), $namespaces );

		$usageUpdater = $this->getUsageUpdater();

		return new DataUpdateHookHandlers(
			$namespaceChecker,
			$usageUpdater
		);
	}

	/**
	 * @param bool $expectedDataUpdateCount
	 *
	 * @return ParserOutput
	 */
	private function newParserOutput( $expectedDataUpdateCount ) {
		$output = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$output->expects( $this->exactly( $expectedDataUpdateCount ) )
			->method( 'addSecondaryDataUpdate' );

		return $output;
	}

	/**
	 * @param Title $title
	 * @param bool $expectedDataUpdateCount
	 *
	 * @return Parser
	 */
	private function newParser( Title $title, $expectedDataUpdateCount ) {
		$options = new ParserOptions();
		$output = $this->newParserOutput( $expectedDataUpdateCount );

		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		$parser->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$parser->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( $options ) );

		$parser->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $output ) );

		return $parser;
	}

	public function testNewFromGlobalState() {
		$handler = DataUpdateHookHandlers::newFromGlobalState();
		$this->assertInstanceOf( 'Wikibase\Client\Hooks\DataUpdateHookHandlers', $handler );
	}

	public function parserAfterParseProvider() {
		return array(
			'usage' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				array( 'sitelinks' => array( new ItemId( 'Q1' ) ) ),
			),

			'no usage' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				array(),
			),

			'ignored-namespace' => array(
				Title::makeTitle( NS_USER, 'Foo' ),
				null,
			),
		);
	}

	/**
	 * @dataProvider parserAfterParseProvider
	 */
	public function testDoParserAfterParse( Title $title, $usage ) {
		$parser = $this->newParser( $title, $usage === null ? 0 : 1 );
		$handler = $this->newDataUpdateHookHandlers();

		$text = '';
		$stripState = new StripState( 'x' );

		$handler->doParserAfterParse( $parser, $text, $stripState );

		// Assertions are done by the ParserOutput mock
		$dataUpdates = $parser->getOutput()->getSecondaryDataUpdates( $title );
	}

}
