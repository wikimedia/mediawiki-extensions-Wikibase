<?php

namespace Wikibase\Repo\Tests\Specials;

use Language;
use Message;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;

/**
 * @covers Wikibase\Repo\Specials\SpecialPageCopyrightView
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SpecialPageCopyrightViewTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider getHtmlProvider
	 */
	public function testGetHtml( $expected, Message $message, $languageCode ) {
		$lang = Language::factory( $languageCode );

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
		$copyrightMessageBuilder = $this->getMockBuilder( CopyrightMessageBuilder::class )
			->getMock();

		$copyrightMessageBuilder->expects( $this->any() )
			->method( 'build' )
			->will( $this->returnValue( $message ) );

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
				'qqx'
			]
		];
	}

}
