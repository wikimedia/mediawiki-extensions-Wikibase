<?php

namespace Wikibase\View\Template\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers Wikibase\View\Template\TemplateFactory
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
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
		$this->assertInstanceOf( 'Wikibase\View\Template\TemplateFactory', $instance );
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

	public function testRender() {
		$rendered = $this->newInstance()->render( 'basic', '<PARAM>' );
		$this->assertSame( '<PARAM>', $rendered );
	}

}
