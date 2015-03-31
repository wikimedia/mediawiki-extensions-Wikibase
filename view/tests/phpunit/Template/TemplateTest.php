<?php

namespace Wikibase\View\Template\Test;

use Wikibase\View\Template\Template;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */
class TemplateTest extends \MediaWikiTestCase {

	/**
	 * @group WikibaseLib
	 * @dataProvider providerText
	 */
	public function testText( $html ) {
		$registry = new TemplateRegistry();
		$registry->addTemplate( 'tmpl1', $html );

		$template = new Template( $registry, 'tmpl1', array( 'param' ) );
		$this->assertHTMLEquals( $template->text(), '<div>param</div>' );
	}

	public function providerText() {
		return array(
			array( '<div>$1</div>' )
		);
	}

}
