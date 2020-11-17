<?php

namespace Wikibase\Repo\Tests\Specials;

use MediaWiki\MediaWikiServices;
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

		$this->setContentLang( 'qqx' );

		$services = MediaWikiServices::getInstance();
		$services->resetServiceForTesting( 'TitleFormatter' );
		$services->resetServiceForTesting( 'TitleParser' );
		$services->resetServiceForTesting( '_MediaWikiTitleCodec' );
	}

	protected function tearDown(): void {
		parent::tearDown();

		$services = MediaWikiServices::getInstance();
		$services->resetServiceForTesting( 'TitleFormatter' );
		$services->resetServiceForTesting( 'TitleParser' );
		$services->resetServiceForTesting( '_MediaWikiTitleCodec' );
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
		list( $output, ) = $this->executeSpecialPage( '', null, 'qqx' );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'wikibase-listdatatypes-summary', $output );
		$this->assertStringContainsString( 'wikibase-listdatatypes-intro', $output );

		$this->assertStringContainsString( 'id="wikibase-item"', $output );
		$this->assertStringContainsString( 'wikibase-listdatatypes-listproperties', $output );
		$this->assertStringContainsString( 'Special:ListProperties/wikibase-item', $output );
	}

}
