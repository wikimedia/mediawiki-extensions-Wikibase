<?php

namespace Wikibase\Test;
use Wikibase\HtmlTemplateStore as HtmlTemplateStore;
use Wikibase\HtmlTemplate as HtmlTemplate;

/**
 * Tests for the Wikibase\HtmlTemplateStore class.
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
 * @author H. Snater <mediawiki@sater.com>
 */
class HtmlTemplateStoreTest extends \MediaWikiTestCase {

	/**
	 * @group WikibaseLib
	 * @dataProvider providerFillingHtmlTemplateStore
	 */
	public function testFillingHtmlTemplateStore( $html ) {
		HtmlTemplateStore::singleton()->addTemplate( 'tmpl1', $html );
		$this->assertEquals(
			HtmlTemplateStore::singleton()->getTemplate( 'tmpl1' ),
			$html
		);
	}

	/**
	 * @group WikibaseLib
	 * @dataProvider providerFillingHtmlTemplateStore
	 */
	public function testHtmlTemplate( $html ) {
		HtmlTemplateStore::singleton()->addTemplate( 'tmpl1', $html );
		$template = new HtmlTemplate( 'tmpl1', array( 'param' ) );
		$this->assertHTMLEquals( $template->text(), str_replace( '$1', 'param', $html ) );
	}

	public function providerFillingHtmlTemplateStore() {
		return array(
			array( '<div>$1</div>' )
		);
	}


	/**
	 * @group WikibaseLib
	 * @dataProvider providerFillingHtmlTemplateStoreMultiple
	 */
	public function testFillingHtmlTemplateStoreMultiple( $data ) {
		HtmlTemplateStore::singleton()->addTemplates( $data );

		$templates = HtmlTemplateStore::singleton()->getTemplates();
		foreach( $data AS $key => $html ) {
			$this->assertEquals(
				$templates[$key],
				$html
			);
		}
	}

	public function providerFillingHtmlTemplateStoreMultiple() {
		return array(
			array(
				array( 'tmpl2' => '<div>$1</div>' ),
				array( 'tmpl3' => '<div><div>$1</div></div>' )
			)
		);
	}

}
