<?php

namespace Wikibase\Test;

use Wikibase\Client\Hooks\BaseTemplateAfterPortletHandler;

/**
 * @covers Wikibase\Client\Hooks\BaseTemplateAfterPortletHandler
 *
 * @group WikibaseClient
 * @group WikibaseHooks
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class BaseTemplateAfterPortletHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getEditLinkProvider
	 */
	public function testGetEditLink( $expect, $link, $name ) {
		$template = $this->getMockBuilder( 'BaseTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$template->expects( $this->any() )
			->method( 'get' )
			->with( 'wbeditlanglinks' )
			->will( $this->returnValue( $link ) );

		$handler = new BaseTemplateAfterPortletHandler();
		$return = $handler->getEditLink( $template, $name );

		$this->assertSame( $expect, $return );
	}

	public function getEditLinkProvider() {
		return array(
			array( null, 'foo', 'bar' ),
			array( 'foo', 'foo', 'lang' ),
		);
	}

}
