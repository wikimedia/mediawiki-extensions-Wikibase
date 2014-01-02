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
	public function testGetMessage( $expected, $rightsUrl, $rightsText ) {
		$copyrightMessage = new CopyrightMessage( Language::factory( 'qqx' ) );
		$actual = $copyrightMessage->getMessage( $rightsUrl, $rightsText );

		$this->assertEquals( $expected, $actual );
	}

	public function getMessageProvider() {
		return array(
			array(
				'(wikibase-shortcopyrightwarning: (wikibase-save), (copyrightpage), '
				. '<a rel="nofollow" class="external text" href="https://creativecommons.org">'
				. 'Creative Commons Attribution-Share Alike 3.0</a>)',
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
