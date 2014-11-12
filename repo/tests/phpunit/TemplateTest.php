<?php

namespace Wikibase\Test;

use Wikibase\Template;
use Wikibase\TemplateRegistry;

/**
 * @covers Wikibase\Template
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
		$registry = new TemplateRegistry();
		$registry->addTemplate( 'tmpl1', $html );

		$template = new Template( $registry, 'tmpl1', array( 'param' ) );
		$this->assertHTMLEquals( $template->text(), '<div>param</div>' );
	}

	public static function providerText() {
		return array(
			array( '<div>$1</div>' )
		);
	}

}
