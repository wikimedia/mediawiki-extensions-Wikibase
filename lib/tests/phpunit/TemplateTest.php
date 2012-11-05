<?php

namespace Wikibase\Test;
use Wikibase\TemplateStore;
use Wikibase\Template;

/**
 * Tests for the Wikibase\Template class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
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
		TemplateStore::singleton()->addTemplate( 'tmpl1', $html );
		$template = new Template( 'tmpl1', array( 'param' ) );
		$this->assertHTMLEquals( $template->text(), '<div>param</div>' );
	}

	public function providerText() {
		return array(
			array( '<div>$1</div>' )
		);
	}

}
