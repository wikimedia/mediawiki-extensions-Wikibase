<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
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
 */
class BaseTemplateAfterPortletHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider makeEditLinkProvider
	 */
	public function testMakeEditLink( $expected, $link, $name ) {
		$template = $this->getMockBuilder( 'BaseTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$template->expects( $this->any() )
			->method( 'get' )
			->with( 'wbeditlanglinks' )
			->will( $this->returnValue( $link ) );

		$handler = new BaseTemplateAfterPortletHandler();
		$formattedLink = $handler->makeEditLink( $template, $name );

		if ( $expected === null ) {
			$this->assertNull( $formattedLink );
		} else {
			MediaWikiTestCase::assertTag( $expected, $formattedLink );
		}
	}

	public function makeEditLinkProvider() {
		$link = $this->getLink();

		$matcher = array(
			'tag' => 'span',
			'attributes' => array(
				'class' => 'wb-langlinks-edit wb-langlinks-link'
			),
			'child' => array(
				'tag' => 'a',
				'attributes' => array(
					'title' => $link['title'],
					'class' => $link['class']
				),
				'content' => $link['text']
			)
		);

		return array(
			array( null, $link, 'search' ),
			array( $matcher, $link, 'lang' )
		);
	}

	private function getLink() {
		return array(
			'action' => 'edit',
			'href' => 'https://www.wikidata.org/wiki/Q2#sitelinks-wikipedia',
			'text' => 'Edit links',
			'title' => 'edit the links!',
			'class' => 'wbc-editpage'
		);
	}

}
