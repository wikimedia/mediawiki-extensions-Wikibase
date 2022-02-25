<?php

namespace Wikibase\Repo\Tests;

use Language;
use MediaWikiIntegrationTestCase;
use Message;
use Wikibase\Repo\CopyrightMessageBuilder;

/**
 * @covers \Wikibase\Repo\CopyrightMessageBuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class CopyrightMessageBuilderTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->setContentLang( 'qqx' );
	}

	/**
	 * @dataProvider buildShortCopyrightWarningMessageProvider
	 */
	public function testBuildShortCopyrightWarningMessage( $expectedKey, $expectedParams,
		$rightsUrl, $rightsText
	) {
		$language = Language::factory( 'qqx' );
		$messageBuilder = new CopyrightMessageBuilder();
		$message = $messageBuilder->build( $rightsUrl, $rightsText, $language );

		$this->assertEquals( $expectedKey, $message->getKey() );
		$this->assertEquals( $expectedParams, $message->getParams() );
	}

	public function buildShortCopyrightWarningMessageProvider() {
		return [
			[
				'wikibase-shortcopyrightwarning',
				[
					'(wikibase-save)',
					'(copyrightpage)',
					Message::plaintextParam( 'https://creativecommons.org' ),
					Message::plaintextParam( 'Creative Commons Attribution-Share Alike 3.0' ),
				],
				'https://creativecommons.org',
				'Creative Commons Attribution-Share Alike 3.0'
			]
		];
	}

}
