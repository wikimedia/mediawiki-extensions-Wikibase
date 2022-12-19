<?php

namespace Wikibase\Repo\Tests\Specials;

use MediaWikiIntegrationTestCase;
use Message;
use Wikibase\Repo\CopyrightMessageBuilder;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;

/**
 * @covers \Wikibase\Repo\Specials\SpecialPageCopyrightView
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SpecialPageCopyrightViewTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider getHtmlProvider
	 */
	public function testGetHtml( $expected, Message $message, $languageCode ) {
		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( $languageCode );

		$specialPageCopyrightView = new SpecialPageCopyrightView(
			$this->getCopyrightMessageBuilder( $message ), 'x', 'y'
		);

		$html = $specialPageCopyrightView->getHtml( $lang, 'wikibase-submit' );
		$this->assertEquals( $expected, $html );
	}

	/**
	 * @param Message $message
	 *
	 * @return CopyrightMessageBuilder
	 */
	private function getCopyrightMessageBuilder( Message $message ) {
		$copyrightMessageBuilder = $this->createMock( CopyrightMessageBuilder::class );

		$copyrightMessageBuilder->method( 'build' )
			->willReturn( $message );

		return $copyrightMessageBuilder;
	}

	public function getHtmlProvider() {
		return [
			[
				'<div>(wikibase-shortcopyrightwarning: wikibase-submit, copyrightpage, copyrightlink)</div>',
				wfMessage(
					'wikibase-shortcopyrightwarning',
					'wikibase-submit',
					'copyrightpage',
					'copyrightlink'
				),
				'qqx',
			],
		];
	}

}
