<?php

namespace Wikibase\Client\Tests\Hooks;

use BaseTemplate;
use PHPUnit4And6Compat;
use Wikibase\Client\Hooks\BaseTemplateAfterPortletHandler;

/**
 * @covers Wikibase\Client\Hooks\BaseTemplateAfterPortletHandler
 *
 * @group WikibaseClient
 * @group WikibaseHooks
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class BaseTemplateAfterPortletHandlerTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider getEditLinkProvider
	 */
	public function testGetEditLink( $expect, $link, $name ) {
		$template = $this->getMockBuilder( BaseTemplate::class )
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
		return [
			[ null, 'foo', 'bar' ],
			[ 'foo', 'foo', 'lang' ],
		];
	}

}
