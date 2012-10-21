<?php

namespace Wikibase\Test;
use Wikibase\HtmlTemplateStore;

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
 * @author H. Snater <mediawiki@snater.com>
 */
class HtmlTemplateStoreTest extends \MediaWikiTestCase {

	/**
	 * @group WikibaseLib
	 * @dataProvider providerAddTemplate
	 */
	public function testAddTemplate( $html ) {
		HtmlTemplateStore::singleton()->addTemplate( 'tmpl1', $html );
		$this->assertEquals(
			HtmlTemplateStore::singleton()->getTemplate( 'tmpl1' ),
			$html
		);
	}

	public function providerAddTemplate() {
		return array(
			array( '<div>$1</div>' )
		);
	}


	/**
	 * @group WikibaseLib
	 * @dataProvider providerAddTemplates
	 */
	public function testAddTemplates( $data ) {
		HtmlTemplateStore::singleton()->addTemplates( $data );

		$templates = HtmlTemplateStore::singleton()->getTemplates();
		foreach( $data AS $key => $html ) {
			$this->assertEquals(
				$templates[$key],
				$html
			);
		}
	}

	public function providerAddTemplates() {
		return array(
			array(
				array( 'tmpl2' => '<div>$1</div>' ),
				array( 'tmpl3' => '<div><div>$1</div></div>' )
			)
		);
	}

}
