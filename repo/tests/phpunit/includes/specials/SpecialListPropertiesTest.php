<?php

namespace Wikibase\Test;

use Language;
use Wikibase\Repo\Specials\SpecialListProperties;
use Wikibase\Test\SpecialPageTestBase;

/**
 * @covers Wikibase\Repo\Specials\SpecialListProperties
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialListPropertiesTest extends SpecialPageTestBase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( array(
			'wgContLang' => Language::factory( 'qqx' )
		) );
	}

	protected function newSpecialPage() {
		return new SpecialListProperties();
	}

	public function testExecute() {
		// This also tests that there is no fatal error, that the restriction handling is working
		// and doesn't block. That is, the default should let the user execute the page.
		list( $output, ) = $this->executeSpecialPage( '' );

		$this->assertInternalType( 'string', $output );
		$this->assertContains( 'wikibase-listproperties-summary', $output );
		$this->assertContains( 'wikibase-listproperties-legend', $output );

		list( $output, ) = $this->executeSpecialPage( 'url' );

		$this->assertContains( 'specialpage-empty', $output );
	}

}

