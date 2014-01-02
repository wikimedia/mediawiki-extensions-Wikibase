<?php

namespace Wikibase\Test;

use Language;
use Wikibase\CopyrightMessageBuilder;

/**
 * @covers Wikibase\CopyrightMessageBuilder
 *
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class CopyrightMessageBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getBuildProvider
	 */
	public function testBuild( $expectedKey, $expectedParams, $rightsUrl, $rightsText ) {
		$language = Language::factory( 'qqx' );
		$messageBuilder = new CopyrightMessageBuilder();
		$message = $messageBuilder->build( $rightsUrl, $rightsText, $language );

		$this->assertEquals( $expectedKey, $message->getKey() );
		$this->assertEquals( $expectedParams, $message->getParams() );
	}

	public function getBuildProvider() {
		return array(
			array(
				'wikibase-shortcopyrightwarning',
				array(
					'(wikibase-save)',
					'(copyrightpage)',
					'[https://creativecommons.org Creative Commons Attribution-Share Alike 3.0]'
				),
				'https://creativecommons.org',
				'Creative Commons Attribution-Share Alike 3.0'
			)
		);
	}

}

