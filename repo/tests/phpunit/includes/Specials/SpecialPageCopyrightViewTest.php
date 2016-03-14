<?php

namespace Wikibase\Repo\Tests\Specials;

use Language;
use Message;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;

/**
 * @covers Wikibase\Repo\Specials\SpecialPageCopyrightView
 *
 * @group Wikibase
 * @group WikibaseRepo
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

	private function getCopyrightMessageBuilder( Message $message ) {
		$copyrightMessageBuilder = $this->getMockBuilder( 'Wikibase\CopyrightMessageBuilder' )
			->getMock();

		$copyrightMessageBuilder->expects( $this->any() )
			->method( 'build' )
			->will( $this->returnValue( $message ) );

		return $copyrightMessageBuilder;
	}

	public function getHtmlProvider() {
		return array(
			array(
				'<div>(wikibase-shortcopyrightwarning: wikibase-submit, copyrightpage, copyrightlink)</div>',
				wfMessage(
					'wikibase-shortcopyrightwarning',
					'wikibase-submit',
					'copyrightpage',
					'copyrightlink'
				),
				'qqx'
			)
		);
	}

}
