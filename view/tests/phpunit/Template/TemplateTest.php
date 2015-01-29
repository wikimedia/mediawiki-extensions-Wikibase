<?php

namespace Wikibase\View\Tests\Template;

use Wikibase\View\Template\Template;

/**
 * @covers Wikibase\View\Template\Template
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author H. Snater <mediawiki@snater.com>
 * @author Thiemo Kreuz
 */
class TemplateTest extends \PHPUnit\Framework\TestCase {

	public function testGivenEmptyTemplate_renderReturnsPlaceholder() {
		$instance = new Template( 'empty', '' );
		$rendered = $instance->render();
		$this->assertSame( '<empty>', $rendered );
	}

	public function testGivenHtml_renderDoesNotEscapeHtml() {
		$template = new Template( 'tmpl1', '<a>$1</a>', [ '<PARAM>' ] );

		$this->assertSame( '<a><PARAM></a>', $template->render() );
	}

	public function testGivenTemplateSyntax_renderDoesNotExpandTemplates() {
		$template = new Template( 'tmpl1', '{{$1}}', [ '<PARAM>' ] );

		$this->assertSame( '{{<PARAM>}}', $template->render() );
	}

	public function testText() {
		$template = new Template( 'tmpl1', '<div>$1</div>', [ 'param' ] );
		$this->assertSame( '<div>param</div>', $template->text() );
	}

}
