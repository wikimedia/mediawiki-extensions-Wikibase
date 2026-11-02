<?php

namespace Wikibase\Repo\Tests;

use MediaWiki\MainConfigNames;
use MediaWikiIntegrationTestCase;
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
		$rightsUrl, $rightsText, $mobileEditing, $publish
	) {
		$this->overrideConfigValue( MainConfigNames::EditSubmitButtonLabelPublish, $publish );
		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );
		$messageBuilder = new CopyrightMessageBuilder();
		$message = $messageBuilder->build(
			$rightsUrl,
			$rightsText,
			$language,
			null,
			$mobileEditing
		);

		$this->assertEquals( $expectedKey, $message->getKey() );
		$this->assertEquals( $expectedParams, $message->getParams() );
	}

	public static function buildShortCopyrightWarningMessageProvider() {
		return [
			'desktop editing, with copyright url and text, save button' => [
				'wikibase-shortcopyrightwarning',
				[
					'(wikibase-save)',
					'(copyrightpage)',
					'https://creativecommons.org',
					'Creative Commons Attribution-Share Alike 3.0',
				],
				'https://creativecommons.org',
				'Creative Commons Attribution-Share Alike 3.0',
				false,
				false,
			],
			'desktop editing, with copyright url and text, publish button' => [
				'wikibase-shortcopyrightwarning',
				[
					'(wikibase-publish)',
					'(copyrightpage)',
					'https://creativecommons.org',
					'Creative Commons Attribution-Share Alike 3.0',
				],
				'https://creativecommons.org',
				'Creative Commons Attribution-Share Alike 3.0',
				false,
				true,
			],
			'desktop editing, without copyright url and text, save button' => [
				'wikibase-shortcopyrightwarning-minimal',
				[
					'(wikibase-save)',
					'(copyrightpage)',
				],
				'',
				'',
				false,
				false,
			],
			'desktop editing, without copyright url and text, publish button' => [
				'wikibase-shortcopyrightwarning-minimal',
				[
					'(wikibase-publish)',
					'(copyrightpage)',
				],
				'',
				'',
				false,
				true,
			],
			// publish vs save makes no difference for mobile editing
			'mobile editing, without copyright url or text' => [
				'wikibase-copyrightdialog-warning-minimal',
				[ '(copyrightpage)' ],
				'',
				'',
				true,
				true,
			],
			'mobile editing, with copyright url and text' => [
				'wikibase-copyrightdialog-warning',
				[
					'(copyrightpage)',
					'https://creativecommons.org',
					'Creative Commons Attribution-Share Alike 3.0',
				],
				'https://creativecommons.org',
				'Creative Commons Attribution-Share Alike 3.0',
				true,
				true,
			],
		];
	}

}
