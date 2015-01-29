<?php

namespace Wikibase\View\Template\Test;

use Wikibase\View\Template\Template;

/**
 * @covers Wikibase\View\Template\Template
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 * @author Thiemo Mättig
 */
class TemplateTest extends \MediaWikiTestCase {

	public function testRender() {
		$template = new Template( 'tmpl1', '<a>$1</a>', array( 'A' ) );

		$this->assertSame( '<a>A</a>', $template->render() );
	}

	public function testGivenTemplateSyntax_renderDoesNotExpandTemplates() {
		$template = new Template( 'tmpl1', '{{$1}}', array( 'A' ) );

		$this->assertSame( '{{A}}', $template->render() );
	}

}
