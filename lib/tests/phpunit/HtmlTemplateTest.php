<?php

namespace Wikibase\Test;
use Wikibase\HtmlTemplateStore as HtmlTemplateStore;
use Wikibase\HtmlTemplate as HtmlTemplate;

/**
 * Tests for the Wikibase\HtmlTemplate class.
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
class HtmlTemplateTest extends \MediaWikiTestCase {

	/**
	 * @group WikibaseLib
	 * @dataProvider providerText
	 */
	public function testText( $html ) {
		HtmlTemplateStore::singleton()->addTemplate( 'tmpl1', $html );
		$template = new HtmlTemplate( 'tmpl1', array( 'param' ) );
		$this->assertHTMLEquals( $template->text(), str_replace( '$1', 'param', $html ) );
	}

	public function providerText() {
		return array(
			array( '<div>$1</div>' )
		);
	}

}
