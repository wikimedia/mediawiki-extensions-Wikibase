<?php

namespace Wikibase\Test;

use Language;
use Message;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;

/**
 * @covers Wikibase\Repo\Specials\SpecialPageCopyrightView
 *
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SpecialPageCopyrightViewTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getHtmlProvider
	 */
	public function testGetHtml( $expected, $message, $languageCode ) {
		$lang = Language::factory( $languageCode );

		$specialPageCopyrightView = new SpecialPageCopyrightView(
			$this->getCopyrightMessageBuilder( $message ), 'x', 'y'
		);

		$html = $specialPageCopyrightView->getHtml( $lang );
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
		$message = new Message(
			'wikibase-shortcopyrightwarning',
			array( 'wikibase-save', 'copyrightpage', 'copyrightlink' )
		);

		return array(
			array(
				'<div>(wikibase-shortcopyrightwarning: wikibase-save, copyrightpage, copyrightlink)</div>',
				$message,
				'qqx'
			)
		);
	}

}

