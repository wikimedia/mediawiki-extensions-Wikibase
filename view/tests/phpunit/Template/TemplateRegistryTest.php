<?php

namespace Wikibase\View\Template\Test;

use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class TemplateRegistryTest extends \MediaWikiTestCase {

	public function testCanConstructWithEmptyArray() {
		$registry = new TemplateRegistry( array() );
		$this->assertSame( array(), $registry->getTemplates() );
	}

	public function testRemovesTabs() {
		$registry = new TemplateRegistry( array( 'known' => "no\ttabs" ) );
		$this->assertSame( 'notabs', $registry->getTemplate( 'known' ) );
	}

	public function testGetTemplates() {
		$registry = new TemplateRegistry( array( 'known' => 'html' ) );
		$this->assertSame( array( 'known' => 'html' ), $registry->getTemplates() );
	}

	public function testGetKnownTemplate() {
		$registry = new TemplateRegistry( array( 'known' => 'html' ) );
		$this->assertSame( 'html', $registry->getTemplate( 'known' ) );
	}

	public function testGetUnknownTemplate() {
		$registry = new TemplateRegistry( array() );

		\MediaWiki\suppressWarnings();
		$html = $registry->getTemplate( 'unknown' );
		\MediaWiki\restoreWarnings();

		$this->assertNull( $html );
	}

}
