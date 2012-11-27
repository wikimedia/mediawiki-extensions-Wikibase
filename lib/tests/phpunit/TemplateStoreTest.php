<?php

namespace Wikibase\Test;
use Wikibase\TemplateStore;

/**
 * Tests for the Wikibase\TemplateStore class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */
class TemplateStoreTest extends \MediaWikiTestCase {

	/**
	 * @group WikibaseLib
	 * @dataProvider providerAddTemplate
	 */
	public function testAddTemplate( $html ) {
		TemplateStore::singleton()->addTemplate( 'tmpl1', $html );
		$this->assertEquals(
			TemplateStore::singleton()->getTemplate( 'tmpl1' ),
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
		TemplateStore::singleton()->addTemplates( $data );

		$templates = TemplateStore::singleton()->getTemplates();
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
