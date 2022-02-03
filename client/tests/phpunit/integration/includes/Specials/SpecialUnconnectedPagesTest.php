<?php

namespace Wikibase\Client\Tests\Integration\Specials;

use SpecialPageTestBase;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\Specials\SpecialUnconnectedPages;
use Wikibase\Client\WikibaseClient;

/**
 * @covers \Wikibase\Client\Specials\SpecialUnconnectedPages
 *
 * @group WikibaseClient
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Thiemo Kreuz
 */
class SpecialUnconnectedPagesTest extends SpecialPageTestBase {

	protected function newSpecialPage( NamespaceChecker $namespaceChecker = null ) {
		$services = $this->getServiceContainer();
		return new SpecialUnconnectedPages(
			$services->getNamespaceInfo(),
			$services->getTitleFactory(),
			WikibaseClient::getClientDomainDbFactory( $services ),
			$namespaceChecker ?: WikibaseClient::getNamespaceChecker( $services )
		);
	}

	public function testExecuteDoesNotCauseFatalError() {
		$this->executeSpecialPage( '' );
		$this->assertTrue( true, 'Calling execute without any subpage value' );
	}

	/**
	 * @dataProvider provideBuildConditionals
	 */
	public function testBuildConditionals( ?int $ns, array $expected ) {
		$checker = new NamespaceChecker( [ 2 ], [ 0, 4 ] );
		$page = $this->newSpecialPage( $checker );
		$page->getRequest()->setVal( 'namespace', $ns );
		$this->assertEquals( $expected, $page->buildConditionals() );
	}

	public function provideBuildConditionals() {
		yield 'no namespace' => [ null, [ 'page_namespace IN (0,4)' ] ];
		yield 'included namespace' => [ 0, [ 'page_namespace = 0' ] ];
		yield 'excluded namespace' => [ 2, [ 'page_namespace IN (0,4)' ] ];
	}

	public function testGetQueryInfo() {
		$page = $this->newSpecialPage();
		$queryInfo = $page->getQueryInfo();
		$this->assertIsArray( $queryInfo );
		$this->assertNotEmpty( $queryInfo );
		$this->assertStringContainsString(
			'expectedUnconnectedPage',
			json_encode( $queryInfo['join_conds']['page_props'] )
		);
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

	public function testFormatResult() {
		$skin = $this->createMock( \Skin::class );
		$result = new \stdClass();
		$result->value = 1;

		$namespaceChecker = new NamespaceChecker( [] );

		$titleFactoryMock = $this->getMockBuilder( \TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$titleFactoryMock->method( 'newFromID' )
			->willReturn( null );

		$services = $this->getServiceContainer();
		$specialPage = new SpecialUnconnectedPages(
			$services->getNamespaceInfo(),
			$titleFactoryMock,
			WikibaseClient::getClientDomainDbFactory( $services ),
			$namespaceChecker ?: WikibaseClient::getNamespaceChecker( $services )
		);

		$this->assertFalse( $specialPage->formatResult( $skin, $result ) );
	}

}
