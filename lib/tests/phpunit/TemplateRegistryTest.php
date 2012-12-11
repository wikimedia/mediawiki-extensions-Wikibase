<?php

namespace Wikibase\Test;
use Wikibase\TemplateRegistry;

/**
 * Tests for the Wikibase\TemplateRegistry class.
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
class TemplateRegistryTest extends \MediaWikiTestCase {

	/**
	 * @group WikibaseLib
	 * @dataProvider providerAddTemplate
	 */
	public function testAddTemplate( $html ) {
		$registry = new TemplateRegistry();
		$registry->addTemplate( 'tmpl1', $html );

		$this->assertEquals(
			$registry->getTemplate( 'tmpl1' ),
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
		$registry = new TemplateRegistry();

		$registry->addTemplates( $data );

		$templates = $registry->getTemplates();
		foreach( $data as $key => $html ) {
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
