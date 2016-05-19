<?php

namespace Wikibase\Repo\Tests\Specials;

use Language;
use MediaWiki\MediaWikiServices;
use SpecialPageTestBase;
use Wikibase\Repo\Specials\SpecialListDatatypes;

/**
 * @covers Wikibase\Repo\Specials\SpecialListDatatypes
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class SpecialListDatatypesTest extends SpecialPageTestBase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( array(
			'wgContLang' => Language::factory( 'qqx' )
		) );
		$services = MediaWikiServices::getInstance();
		$services->resetServiceForTesting( 'TitleFormatter' );
		$services->resetServiceForTesting( 'TitleParser' );
		$services->resetServiceForTesting( '_MediaWikiTitleCodec' );
	}

	protected function tearDown() {
		parent::tearDown();

		$services = MediaWikiServices::getInstance();
		$services->resetServiceForTesting( 'TitleFormatter' );
		$services->resetServiceForTesting( 'TitleParser' );
		$services->resetServiceForTesting( '_MediaWikiTitleCodec' );
	}

	protected function newSpecialPage() {
		return new SpecialListDatatypes();
	}

	public function testExecute() {
		// This also tests that there is no fatal error, that the restriction handling is working
		// and doesn't block. That is, the default should let the user execute the page.
		list( $output, ) = $this->executeSpecialPage( '' );

		$this->assertInternalType( 'string', $output );
		$this->assertContains( 'wikibase-listdatatypes-summary', $output );
		$this->assertContains( 'wikibase-listdatatypes-intro', $output );

		$this->assertContains( 'wikibase-item', $output );
		$this->assertContains( 'wikibase-listdatatypes-listproperties', $output );
		$this->assertContains( 'Special:ListProperties/wikibase-item', $output );
	}

}
