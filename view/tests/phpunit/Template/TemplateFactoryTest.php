<?php

namespace Wikibase\View\Tests\Template;

use PHPUnit_Framework_TestCase;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\Template\TemplateFactory
 *
 * @uses Wikibase\View\Template\Template
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class TemplateFactoryTest extends PHPUnit_Framework_TestCase {

	private function newInstance() {
		return new TemplateFactory( [ 'basic' => '$1' ] );
	}

	public function testGetDefaultInstance() {
		$instance = TemplateFactory::getDefaultInstance();

		$this->assertInstanceOf( TemplateFactory::class, $instance );
		$this->assertNotEmpty( $instance->getTemplates() );
	}

	public function testRemovesTabs() {
		$factory = new TemplateFactory( [ 'tmpl1' => "no\ttabs" ] );

		$this->assertSame( 'notabs', $factory->render( 'tmpl1' ) );
	}

	public function testRemovesComments() {
		$factory = new TemplateFactory( [
			'tmpl1' => "no<!--[if IE]>IE<![endif]-->comments<!-- <div>\n</div> -->",
		] );

		$this->assertSame( 'nocomments', $factory->render( 'tmpl1' ) );
	}

	public function testGetTemplates() {
		$templates = $this->newInstance()->getTemplates();
		$this->assertSame( [ 'basic' => '$1' ], $templates );
	}

	public function testGivenUnknownTemplate_renderReturnsPlaceholder() {
		$rendered = $this->newInstance()->render( 'unknown' );
		$this->assertSame( '<unknown>', $rendered );
	}

	/**
	 * @dataProvider renderParamsProvider
	 */
	public function testRender( $params, $expected ) {
		$rendered = $this->newInstance()->render( 'basic', $params );
		$this->assertSame( $expected, $rendered );
	}

	public function renderParamsProvider() {
		return [
			'Single parameter' => [ '<PARAM>', '<PARAM>' ],
			'Missing parameter' => [ [ ], '$1' ],
			'Parameter array' => [ [ '<PARAM>' ], '<PARAM>' ],
			'To many parameters' => [ [ '<PARAM>', 'ignored' ], '<PARAM>' ],
		];
	}

}
