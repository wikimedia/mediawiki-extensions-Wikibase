<?php

namespace Wikibase\Repo\Tests\Specials;

use MediaWiki\MainConfigNames;
use SpecialPageTestBase;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Repo\Specials\SpecialListDatatypes;

/**
 * @covers \Wikibase\Repo\Specials\SpecialListDatatypes
 * @covers \Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class SpecialListDatatypesTest extends SpecialPageTestBase {

	protected function setUp(): void {
		parent::setUp();

		// ensure the link to Special:ListProperties is not localized
		$this->overrideConfigValue( MainConfigNames::LanguageCode, 'en' );
	}

	protected function newSpecialPage() {
		$dataTypeDefinitions = new DataTypeDefinitions( [
			'PT:wikibase-item' => [ 'value-type' => 'wikibase-entityid' ],
		] );
		return new SpecialListDatatypes( $dataTypeDefinitions );
	}

	public function testExecute() {
		// This also tests that there is no fatal error, that the restriction handling is working
		// and doesn't block. That is, the default should let the user execute the page.
		[ $output ] = $this->executeSpecialPage(); // note: $output is in uselang=qqx by default

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'wikibase-listdatatypes-summary', $output );
		$this->assertStringContainsString( 'wikibase-listdatatypes-intro', $output );

		$this->assertStringContainsString( 'id="wikibase-item"', $output );
		$this->assertStringContainsString( 'wikibase-listdatatypes-listproperties', $output );
		$this->assertStringContainsString( 'Special:ListProperties/wikibase-item', $output );
	}

}
