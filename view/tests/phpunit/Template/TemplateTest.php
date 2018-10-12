<?php

namespace Wikibase\View\Tests\Template;

use Wikibase\View\Template\Template;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers \Wikibase\View\Template\Template
 *
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author H. Snater <mediawiki@snater.com>
 * @author Thiemo Kreuz
 */
class TemplateTest extends \PHPUnit\Framework\TestCase {

	public function testRender() {
		$instance = new Template( new TemplateRegistry( [ 'empty' => '' ] ), 'empty' );
		$rendered = $instance->render();
		$this->assertSame( '', $rendered );
	}

	public function testText() {
		$registry = new TemplateRegistry( [
			'tmpl1' => '<div>$1</div>',
		] );

		$template = new Template( $registry, 'tmpl1', [ 'param' ] );
		$this->assertSame( '<div>param</div>', $template->text() );
	}

}
