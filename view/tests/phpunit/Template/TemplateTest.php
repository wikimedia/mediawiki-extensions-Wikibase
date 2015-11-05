<?php

namespace Wikibase\View\Template\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\View\Template\Template;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers Wikibase\View\Template\Template
 *
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */
class TemplateTest extends PHPUnit_Framework_TestCase {

	public function testRender() {
		$instance = new Template( new TemplateRegistry( array( 'empty' => '' ) ), 'empty' );
		$rendered = $instance->render();
		$this->assertSame( '', $rendered );
	}

	/**
	 * @dataProvider providerText
	 */
	public function testText( $html ) {
		$registry = new TemplateRegistry();
		$registry->addTemplate( 'tmpl1', $html );

		$template = new Template( $registry, 'tmpl1', array( 'param' ) );
		$this->assertSame( '<div>param</div>', $template->text() );
	}

	public function providerText() {
		return array(
			array( '<div>$1</div>' )
		);
	}

}
