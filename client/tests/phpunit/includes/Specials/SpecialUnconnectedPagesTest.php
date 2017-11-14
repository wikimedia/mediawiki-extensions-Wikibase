<?php

namespace Wikibase\Client\Tests\Specials;

use SpecialPageTestBase;
use Title;
use Wikibase\Client\Specials\SpecialUnconnectedPages;
use Wikibase\Client\NamespaceChecker;

/**
 * @covers Wikibase\Client\Specials\SpecialUnconnectedPages
 *
 * @group WikibaseClient
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Thiemo Kreuz
 */
class SpecialUnconnectedPagesTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialUnconnectedPages();
	}

	public function testExecuteDoesNotCauseFatalError() {
		$this->executeSpecialPage( '' );
		$this->assertTrue( true, 'Calling execute without any subpage value' );
	}

	/**
	 * @dataProvider provideNamespaceChecker
	 */
	public function testNamespaceChecker( $namespace, $expected ) {
		$page = $this->newSpecialPage();
		$checker = new NamespaceChecker( [ 2, 4 ], [ 0 ] );
		$page->setNamespaceChecker( $checker );
		$this->assertEquals( $expected, $page->getNamespaceChecker()->isWikibaseEnabled( $namespace ) );
	}

	public function provideNamespaceChecker() {
		return [
			[ 0, true ],  // #0
			[ 1, false ], // #1
			[ 2, false ], // #2
			[ 3, false ], // #3
			[ 4, false ], // #4
			[ 5, false ], // #5
			[ 6, false ], // #6
			[ 7, false ], // #7
		];
	}

	/**
	 *  @dataProvider provideBuildConditionals
	 */
	public function testBuildConditionals( $text, $expected ) {
		$page = $this->newSpecialPage();
		$title = Title::newFromText( $text );
		$checker = new NamespaceChecker( [ 2, 4 ], [ 0 ] );
		$dbr = wfGetDB( DB_REPLICA );
		$this->assertEquals( $expected, $page->buildConditionals( $dbr, $title, $checker ) );
	}

	public function provideBuildConditionals() {
		return [
			[ 'foo', [ "page_title >= 'Foo'", "page_namespace = 0", 'page_namespace IN (0)' ] ],
			[ ':foo', [ "page_title >= 'Foo'", "page_namespace = 0", 'page_namespace IN (0)' ] ],
			[ 'user:foo', [ "page_title >= 'Foo'", "page_namespace = 2", 'page_namespace IN (0)' ] ],
			[ 'user talk:foo', [ "page_title >= 'Foo'", "page_namespace = 3", 'page_namespace IN (0)' ] ],
		];
	}

	public function testGetQueryInfo() {
		$page = $this->newSpecialPage();
		$queryInfo = $page->getQueryInfo();
		$this->assertInternalType( 'array', $queryInfo );
		$this->assertNotEmpty( $queryInfo );
		$this->assertArrayHasKey( 'conds', $queryInfo );
	}

	public function testReallyDoQueryReturnsEmptyResultWhenExceedingLimit() {
		$page = $this->newSpecialPage();
		$result = $page->reallyDoQuery( 1, 10001 );
		$this->assertSame( 0, $result->numRows() );
	}

	public function testFetchFromCacheReturnsEmptyResultWhenExceedingLimit() {
		$page = $this->newSpecialPage();
		$result = $page->fetchFromCache( 1, 10001 );
		$this->assertSame( 0, $result->numRows() );
	}

}
