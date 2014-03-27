<?php

namespace Wikibase\Test;

use Language;
use Wikibase\CopyrightMessageBuilder;
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
	public function testGetHtml( $regex, $matcher, $rightsUrl, $rightsText ) {
		$lang = Language::factory( 'qqx' );

		$specialPageCopyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$rightsUrl,
			$rightsText
		);

		$html = $specialPageCopyrightView->getHtml( $lang );

		$this->assertRegExp( $regex, $html, 'message html includes wikibase-save and copyrightpage' );
		$this->assertTag( $matcher, $html, 'message html includes license link and text' );
	}

	public function getHtmlProvider() {
		return array(
			array(
				'/\(wikibase-shortcopyrightwarning: \(wikibase-save\), ' .
				preg_quote( wfMessage( 'copyrightpage' )->inContentLanguage()->text(), '/' ) .
				'/',
				array(
					'tag' => 'a',
					'attributes' => array(
						'href' => 'https://creativecommons.org'
					),
					'content' => 'Creative Commons Attribution-Share Alike 3.0'
				),
				'https://creativecommons.org',
				'Creative Commons Attribution-Share Alike 3.0'
			)
		);
	}

}

