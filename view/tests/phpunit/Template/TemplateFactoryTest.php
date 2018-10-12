<?php

namespace Wikibase\View\Tests\Template;

use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers \Wikibase\View\Template\TemplateFactory
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class TemplateFactoryTest extends \PHPUnit\Framework\TestCase {

	private function newInstance() {
		return new TemplateFactory( new TemplateRegistry( [
			'basic' => '$1',
		] ) );
	}

	public function testGetDefaultInstance() {
		$instance = TemplateFactory::getDefaultInstance();
		$this->assertInstanceOf( TemplateFactory::class, $instance );
	}

	public function testGetTemplates() {
		$templates = $this->newInstance()->getTemplates();
		$this->assertSame( [ 'basic' => '$1' ], $templates );
	}

	public function testGet() {
		$template = $this->newInstance()->get( 'basic', [ '<PARAM>' ] );
		$this->assertSame( 'basic', $template->getKey() );
		$this->assertSame( [ '<PARAM>' ], $template->getParams() );
		$this->assertSame( '<PARAM>', $template->plain() );
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
			[ '<PARAM>', '<PARAM>' ],
			[ [], '$1' ],
			[ [ '<PARAM>' ], '<PARAM>' ],
			[ [ '<PARAM>', 'ignored' ], '<PARAM>' ],
		];
	}

}
