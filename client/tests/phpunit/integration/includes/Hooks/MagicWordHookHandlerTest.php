<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWikiIntegrationTestCase;
use Parser;
use ParserOutput;
use Title;
use Wikibase\Client\Hooks\MagicWordHookHandler;
use Wikibase\Client\Hooks\NoLangLinkHandler;
use Wikibase\Lib\SettingsArray;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Client\Hooks\MagicWordHookHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Matthew Flaschen < mflaschen@wikimedia.org >
 */
class MagicWordHookHandlerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider provideGetRepoName
	 */
	public function testGetRepoName( $expected, $langCode, $siteName ) {
		$settings = new SettingsArray();
		$settings->setSetting( 'repoSiteName', $siteName );

		/** @var MagicWordHookHandler $handler */
		$handler = TestingAccessWrapper::newFromObject( new MagicWordHookHandler( $settings ) );

		$actual = $handler->getRepoName(
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( $langCode )
		);

		$this->assertEquals(
			$expected,
			$actual
		);
	}

	// I looked at mocking the messages, but MessageCache
	// is not in ServiceWiring (yet), so these are real messsages,
	// except non-existent-message to test that feature.

	public function provideGetRepoName() {
		return [
			[
				'Client for the Wikibase extension',
				'en',
				'wikibase-client-desc',
			],

			[
				'Cliente para la extensión Wikibase',
				'es',
				'wikibase-client-desc',
			],

			[
				'non-existent-message',
				'en',
				'non-existent-message',
			],
		];
	}

	public function testDoParserGetVariableValueSwitch_wbreponame() {
		$parser = $this->createMock( Parser::class );

		// Configure the stub.
		$parser->method( 'getTargetLanguage' )
			->willReturn( $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' ) );

		$ret = null;

		$settings = new SettingsArray();
		$settings->setSetting( 'repoSiteName', 'wikibase-client-desc' );

		/** @var MagicWordHookHandler $handler */
		$handler = TestingAccessWrapper::newFromObject( new MagicWordHookHandler( $settings ) );

		$cache = [];
		$frame = null;
		call_user_func_array(
			[ $handler, 'onParserGetVariableValueSwitch' ],
			[ $parser, &$cache, 'wbreponame', &$ret, $frame ]
		);

		$this->assertArrayHasKey( 'wbreponame', $cache );
		$this->assertEquals(
			'Client for the Wikibase extension',
			$ret
		);
	}

	public function testDoParserGetVariableValueSwitch_noexternallanglinks() {
		$parser = $this->createMock( Parser::class );

		$parserOutput = new ParserOutput();
		$parser->method( 'getOutput' )
			->willReturn( $parserOutput );
		$parser->method( 'getTitle' )
			->willReturn( Title::newMainPage() );

		/** @var MagicWordHookHandler $handler */
		$handler = TestingAccessWrapper::newFromObject(
			new MagicWordHookHandler( new SettingsArray() )
		);

		$ret = null;
		$cache = [];
		$frame = null;
		call_user_func_array(
			[ $handler, 'onParserGetVariableValueSwitch' ],
			[ $parser, &$cache, 'noexternallanglinks', &$ret, $frame ]
		);

		$this->assertArrayHasKey( 'noexternallanglinks', $cache );
		$this->assertContains(
			'*',
			NoLangLinkHandler::getNoExternalLangLinks( $parserOutput )
		);
	}

}
