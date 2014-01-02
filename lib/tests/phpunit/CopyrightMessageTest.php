<?php

namespace Wikibase\Test;

use Language;
use Wikibase\CopyrightMessage;

/**
 * @covers Wikibase\CopyrightMessage
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class CopyrightMessageTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getMessageProvider
	 */
	public function testGetMessage( $regex, $matcher, $rightsUrl, $rightsText ) {
		$copyrightMessage = new CopyrightMessage( Language::factory( 'qqx' ) );
		$actual = $copyrightMessage->getMessage( $rightsUrl, $rightsText );

		$this->assertRegExp( $regex, $actual, 'message includes wikibase-save and copyrightpage' );
		$this->assertTag( $matcher, $actual, 'message includes license link and text' );
	}

	public function getMessageProvider() {
		return array(
			array(
				'/\(wikibase-shortcopyrightwarning: \(wikibase-save\), \(copyrightpage\)/',
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

	public function testGetVersion() {
		$copyrightMessage = new CopyrightMessage( Language::factory( 'qqx' ) );
		$this->assertEquals( 'wikibase-1', $copyrightMessage->getVersion() );
	}

}
