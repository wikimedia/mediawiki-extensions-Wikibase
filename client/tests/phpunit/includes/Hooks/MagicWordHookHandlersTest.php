<?php

namespace Wikibase\Client\Tests\Hooks;

use Language;
use Wikibase\Client\Hooks\MagicWordHookHandlers;
use MediaWikiTestCase;
use Parser;
use Wikibase\SettingsArray;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers Wikibase\Client\Hooks\MagicWordHookHandlers
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Matthew Flaschen < mflaschen@wikimedia.org >
 */
class MagicWordHookHandlersTest extends MediaWikiTestCase {

	/**
	 * @dataProvider provideGetRepoName
	 */
	public function testGetRepoName( $expected, $langCode, $siteName ) {
		$settings = new SettingsArray();
		$settings->setSetting( 'repoSiteName', $siteName );

		$handler = TestingAccessWrapper::newFromObject(
			new MagicWordHookHandlers( $settings )
		);

		$actual = $handler->getRepoName(
			Language::factory( $langCode )
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

	// TODO: Test noexternallanglinks

	public function testDoParserGetVariableValueSwitch() {
		$parser = $this->getMockBuilder( Parser::class )
			->getMock();

		// Configure the stub.
		$parser->method( 'getTargetLanguage' )
			->willReturn( Language::factory( 'en' ) );

		$ret = null;

		$settings = new SettingsArray();
		$settings->setSetting( 'repoSiteName', 'wikibase-client-desc' );

		$handler = TestingAccessWrapper::newFromObject(
			new MagicWordHookHandlers( $settings )
		);

		$cache = [];
		$word = 'wbreponame';
		call_user_func_array(
			[ $handler, 'doParserGetVariableValueSwitch' ],
			[ &$parser, &$cache, &$word, &$ret ]
		);

		$this->assertEquals(
			'Client for the Wikibase extension',
			$ret
		);
	}

}
