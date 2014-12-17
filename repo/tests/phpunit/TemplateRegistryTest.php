<?php

namespace Wikibase\Test;

use Wikibase\TemplateRegistry;

/**
 * @covers Wikibase\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 * @author Thiemo Mättig
 */
class TemplateRegistryTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider templatesProvider
	 */
	public function testConstructor( $expected ) {
		$registry = new TemplateRegistry( $expected );

		$this->assertEquals( $expected, $registry->getTemplates() );
	}

	/**
	 * @dataProvider templatesProvider
	 */
	public function testAddTemplates( $expected ) {
		$registry = new TemplateRegistry();
		$registry->addTemplates( $expected );

		$this->assertEquals( $expected, $registry->getTemplates() );
	}

	public static function templatesProvider() {
		return array(
			array(
				array( 'tmpl2' => '<div>$1</div>' ),
				array( 'tmpl3' => '<div><div>$1</div></div>' )
			)
		);
	}

	/**
	 * @dataProvider templateProvider
	 */
	public function testAddTemplate( $expected ) {
		$registry = new TemplateRegistry();
		$registry->addTemplate( 'tmpl1', $expected );

		$this->assertEquals( $expected, $registry->getTemplate( 'tmpl1' ) );
	}

	public static function templateProvider() {
		return array(
			array( '<div>$1</div>' )
		);
	}

}
