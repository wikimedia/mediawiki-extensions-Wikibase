<?php

namespace Wikibase\View\Tests\Template;

use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers \Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class TemplateRegistryTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstructWithEmptyArray() {
		$registry = new TemplateRegistry( [] );
		$this->assertSame( [], $registry->getTemplates() );
	}

	public function testRemovesTabs() {
		$registry = new TemplateRegistry( [ 'key' => "no\ttabs" ] );
		$this->assertSame( 'notabs', $registry->getTemplate( 'key' ) );
	}

	public function testRemovesComments() {
		$registry = new TemplateRegistry( [
			'key' => "no<!--[if IE]>IE<![endif]-->comments<!-- <div>\n</div> -->",
		] );
		$this->assertSame( 'nocomments', $registry->getTemplate( 'key' ) );
	}

	public function testGetTemplates() {
		$registry = new TemplateRegistry( [ 'key' => 'html' ] );
		$this->assertSame( [ 'key' => 'html' ], $registry->getTemplates() );
	}

	public function testGetKnownTemplate() {
		$registry = new TemplateRegistry( [ 'key' => 'html' ] );
		$this->assertSame( 'html', $registry->getTemplate( 'key' ) );
	}

	public function testGetUnknownTemplate() {
		$registry = new TemplateRegistry( [] );
		$this->assertNull( @$registry->getTemplate( 'unknown' ) );
	}

}
