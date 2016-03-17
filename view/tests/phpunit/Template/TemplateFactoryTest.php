<?php

namespace Wikibase\View\Tests\Template;

use PHPUnit_Framework_TestCase;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers Wikibase\View\Template\TemplateFactory
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class TemplateFactoryTest extends PHPUnit_Framework_TestCase {

	private function newInstance() {
		return new TemplateFactory( new TemplateRegistry( array(
			'basic' => '$1',
		) ) );
	}

	public function testGetDefaultInstance() {
		$instance = TemplateFactory::getDefaultInstance();
		$this->assertInstanceOf( TemplateFactory::class, $instance );
	}

	public function testGetTemplates() {
		$templates = $this->newInstance()->getTemplates();
		$this->assertSame( array( 'basic' => '$1' ), $templates );
	}

	public function testGet() {
		$template = $this->newInstance()->get( 'basic', array( '<PARAM>' ) );
		$this->assertSame( 'basic', $template->getKey() );
		$this->assertSame( array( '<PARAM>' ), $template->getParams() );
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
		return array(
			array( '<PARAM>', '<PARAM>' ),
			array( array(), '$1' ),
			array( array( '<PARAM>' ), '<PARAM>' ),
			array( array( '<PARAM>', 'ignored' ), '<PARAM>' ),
		);
	}

}
