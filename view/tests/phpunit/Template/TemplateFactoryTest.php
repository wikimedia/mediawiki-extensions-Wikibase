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
			'empty' => '',
		) ) );
	}

	public function testGetDefaultInstance() {
		$instance = TemplateFactory::getDefaultInstance();
		$this->assertInstanceOf( 'Wikibase\View\Template\TemplateFactory', $instance );
	}

	public function testGetTemplates() {
		$instance = $this->newInstance();
		$templates = $instance->getTemplates();
		$this->assertInternalType( 'array', $templates );
		$this->assertContainsOnly( 'string', $templates );
	}

	public function testGet() {
		$instance = $this->newInstance();
		$template = $instance->get( 'empty', array() );
		$this->assertInstanceOf( 'Wikibase\View\Template\Template', $template );
	}

	public function testRender() {
		$instance = $this->newInstance();
		$rendered = $instance->render( 'empty' );
		$this->assertSame( '', $rendered );
	}

}
