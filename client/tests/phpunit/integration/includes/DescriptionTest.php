<?php

namespace Wikibase\Client\Tests\Unit\Api;

use ApiMain;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Title;
use Wikibase\Client\Api\Description;
use Wikibase\Client\Store\DescriptionLookup;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Client\Api\Description
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 */
class DescriptionTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var array[] page id => data
	 */
	private $resultData;

	/**
	 * @var int
	 */
	private $continueEnumParameter;

	protected function setUp(): void {
		parent::setUp();
		$this->resultData = [];
		$this->continueEnumParameter = null;
	}

	/**
	 * @dataProvider provideExecute
	 * @param bool $allowLocalShortDesc
	 * @param bool $forceLocalShortDesc
	 * @param array $params
	 * @param array $requestedPageIds
	 * @param int|null $fitLimit
	 * @param array $expectedPageIds
	 * @param array $expectedSources
	 * @param array $descriptions
	 * @param array $actualSources
	 * @param array $expectedResult
	 * @param int $expectedContinue
	 */
	public function testExecute(
		bool $allowLocalShortDesc,
		bool $forceLocalShortDesc,
		array $params,
		array $requestedPageIds,
		?int $fitLimit,
		array $expectedPageIds,
		array $expectedSources,
		array $descriptions,
		array $actualSources,
		array $expectedResult,
		$expectedContinue
	) {
		$descriptionLookup = $this->getDescriptionLookup( $expectedPageIds, $expectedSources,
			$descriptions, $actualSources );
		$module = $this->getModule( $allowLocalShortDesc, $forceLocalShortDesc, $params,
			$requestedPageIds, $fitLimit, $descriptionLookup );
		$module->execute();
		$this->assertSame( $expectedResult, $this->resultData );
		$this->assertSame( $expectedContinue, $this->continueEnumParameter );
	}

	public function provideExecute() {
		$local = DescriptionLookup::SOURCE_LOCAL;
		$central = DescriptionLookup::SOURCE_CENTRAL;
		return [
			'empty' => [
				'allow local descriptions' => false,
				'force local descriptions' => false,
				'API params' => [],
				'requested page IDs' => [],
				'fit limit' => null,
				'expected page IDs' => [],
				'expected sources' => [ $central ],
				'actual descriptions by page ID' => [],
				'actual sources by page ID' => [],
				'expected results' => [],
				'expected continuation value' => null,
			],
			'local disallowed' => [
				'allow local descriptions' => false,
				'force local descriptions' => false,
				'API params' => [],
				'requested page IDs' => [ 1, 2, 3, 4, 5 ],
				'fit limit' => null,
				'expected page IDs' => [ 1, 2, 3, 4, 5 ],
				'expected sources' => [ $central ],
				'actual descriptions by page ID' => [ 3 => 'C3', 4 => 'C4' ],
				'actual sources by page ID' => [ 3 => $central, 4 => $central ],
				'expected results' => [
					3 => [ 'description' => 'C3', 'descriptionsource' => 'central' ],
					4 => [ 'description' => 'C4', 'descriptionsource' => 'central' ],
				],
				'expected continuation value' => null,
			],
			'prefer local' => [
				'allow local descriptions' => true,
				'force local descriptions' => false,
				'API params' => [],
				'requested page IDs' => [ 1, 2, 3, 4, 5, 6 ],
				'fit limit' => null,
				'expected page IDs' => [ 1, 2, 3, 4, 5, 6 ],
				'expected sources' => [ $local, $central ],
				'actual descriptions by page ID' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3', 4 => 'C4' ],
				'actual sources by page ID' => [ 1 => $local, 2 => $local, 3 => $local, 4 => $central ],
				'expected results' => [
					1 => [ 'description' => 'L1', 'descriptionsource' => 'local' ],
					2 => [ 'description' => 'L2', 'descriptionsource' => 'local' ],
					3 => [ 'description' => 'L3', 'descriptionsource' => 'local' ],
					4 => [ 'description' => 'C4', 'descriptionsource' => 'central' ],
				],
				'expected continuation value' => null,
			],
			'force local' => [
				'allow local descriptions' => true,
				'force local descriptions' => true,
				'API params' => [],
				'requested page IDs' => [ 1, 2, 3, 4, 5, 6 ],
				'fit limit' => null,
				'expected page IDs' => [ 1, 2, 3, 4, 5, 6 ],
				'expected sources' => [ $local ],
				'actual descriptions by page ID' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'actual sources by page ID' => [ 1 => $local, 2 => $local, 3 => $local ],
				'expected results' => [
					1 => [ 'description' => 'L1', 'descriptionsource' => 'local' ],
					2 => [ 'description' => 'L2', 'descriptionsource' => 'local' ],
					3 => [ 'description' => 'L3', 'descriptionsource' => 'local' ],
				],
				'expected continuation value' => null,
			],
			'prefer central' => [
				'allow local descriptions' => true,
				'force local descriptions' => false,
				'API params' => [ 'prefersource' => 'central' ],
				'requested page IDs' => [ 1, 2, 3, 4, 5, 6 ],
				'fit limit' => null,
				'expected page IDs' => [ 1, 2, 3, 4, 5, 6 ],
				'expected sources' => [ $central, $local ],
				'actual descriptions by page ID' => [ 1 => 'L1', 2 => 'L2', 3 => 'C3', 4 => 'C4' ],
				'actual sources by page ID' => [ 1 => $local, 2 => $local, 3 => $central, 4 => $central ],
				'expected results' => [
					1 => [ 'description' => 'L1', 'descriptionsource' => 'local' ],
					2 => [ 'description' => 'L2', 'descriptionsource' => 'local' ],
					3 => [ 'description' => 'C3', 'descriptionsource' => 'central' ],
					4 => [ 'description' => 'C4', 'descriptionsource' => 'central' ],
				],
				'expected continuation value' => null,
			],
			'continuation #1' => [
				'allow local descriptions' => true,
				'force local descriptions' => false,
				'API params' => [],
				'requested page IDs' => [ 1, 2, 3, 4, 5 ],
				'fit limit' => 2,
				'expected page IDs' => [ 1, 2, 3, 4, 5 ],
				'expected sources' => [ $local, $central ],
				'actual descriptions by page ID' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3', 4 => 'C4', 5 => 'C5' ],
				'actual sources by page ID' => [ 1 => $local, 2 => $local, 3 => $local, 4 => $central,
					5 => $central ],
				'expected results' => [
					1 => [ 'description' => 'L1', 'descriptionsource' => 'local' ],
					2 => [ 'description' => 'L2', 'descriptionsource' => 'local' ],
				],
				'expected continuation value' => 2,
			],
			'continuation #2' => [
				'allow local descriptions' => true,
				'force local descriptions' => false,
				'API params' => [ 'continue' => 2 ],
				'requested page IDs' => [ 1, 2, 3, 4, 5 ],
				'fit limit' => 2,
				'expected page IDs' => [ 3, 4, 5 ],
				'expected sources' => [ $local, $central ],
				'actual descriptions by page ID' => [ 3 => 'L3', 4 => 'C4', 5 => 'C5' ],
				'actual sources by page ID' => [ 3 => $local, 4 => $central, 5 => $central ],
				'expected results' => [
					3 => [ 'description' => 'L3', 'descriptionsource' => 'local' ],
					4 => [ 'description' => 'C4', 'descriptionsource' => 'central' ],
				],
				'expected continuation value' => 4,
			],
			'continuation #3' => [
				'allow local descriptions' => true,
				'force local descriptions' => false,
				'API params' => [ 'continue' => 4 ],
				'requested page IDs' => [ 1, 2, 3, 4, 5 ],
				'fit limit' => 2,
				'expected page IDs' => [ 5 ],
				'expected sources' => [ $local, $central ],
				'actual descriptions by page ID' => [ 5 => 'C5' ],
				'actual sources by page ID' => [ 5 => $central ],
				'expected results' => [
					5 => [ 'description' => 'C5', 'descriptionsource' => 'central' ],
				],
				'expected continuation value' => null,
			],
			'continuation with exact fit' => [
				'allow local descriptions' => true,
				'force local descriptions' => false,
				'API params' => [ 'continue' => 2 ],
				'requested page IDs' => [ 1, 2, 3, 4 ],
				'fit limit' => 2,
				'expected page IDs' => [ 3, 4 ],
				'expected sources' => [ $local, $central ],
				'actual descriptions by page ID' => [ 3 => 'L3', 4 => 'C4' ],
				'actual sources by page ID' => [ 3 => $local, 4 => $central ],
				'expected results' => [
					3 => [ 'description' => 'L3', 'descriptionsource' => 'local' ],
					4 => [ 'description' => 'C4', 'descriptionsource' => 'central' ],
				],
				'expected continuation value' => null,
			],
			'limit' => [
				'allow local descriptions' => true,
				'force local descriptions' => false,
				'API params' => [],
				'requested page IDs' => range( 1, 600 ),
				'fit limit' => null,
				'expected page IDs' => range( 1, 500 ),
				'expected sources' => [ $local, $central ],
				'actual descriptions by page ID' => array_fill( 1, 500, 'LX' ),
				'actual sources by page ID' => array_fill( 1, 500, $local ),
				'expected results' => array_fill( 1, 500, [ 'description' => 'LX',
					'descriptionsource' => 'local' ] ),
				'expected continuation value' => 500,
			],
		];
	}

	/**
	 * Mock description lookup.
	 * @param int[] $expectedPageIds
	 * @param string[] $expectedSources
	 * @param array $descriptionsToReturn page ID => description text
	 * @param string[] $sourcesToReturn page ID => DescriptionLookup::SOURCE_*
	 * @return MockObject|DescriptionLookup
	 */
	private function getDescriptionLookup(
		$expectedPageIds,
		$expectedSources,
		$descriptionsToReturn,
		$sourcesToReturn
	) {
		$descriptionLookup = $this->createMock( DescriptionLookup::class );
		$descriptionLookup->expects( $this->once() )
			->method( 'getDescriptions' )
			->willReturnCallback( function ( $titles, $sources, &$actualSources )
				use ( $expectedPageIds, $expectedSources, $descriptionsToReturn, $sourcesToReturn ) {
					/** @var Title[] $titles */
					/** @var string|string[] $sourcesToReturn */
					$pageIds = array_values( array_map( function ( Title $title ) {
						return $title->getArticleID();
					}, $titles ) );
					// Should be a sort-insensitive check but everything is sorted anyway.
					$this->assertSame( $expectedPageIds, $pageIds );
					$this->assertSame( $expectedSources, (array)$sources );
					$actualSources = $sourcesToReturn;
					return $descriptionsToReturn;
			} );
		return $descriptionLookup;
	}

	/**
	 * Create the module, mock ApiBase methods and other API dependencies, have the
	 * mock write results and continuation value into member variables of the test for inspection.
	 *
	 * @param bool $allowLocalShortDesc
	 * @param bool $forceLocalShortDesc
	 * @param array $params API parameters for the module (unprefixed)
	 * @param array $requestedPageIds
	 * @param int|null $fitLimit
	 * @param DescriptionLookup $descriptionLookup
	 * @return MockObject
	 */
	private function getModule(
		bool $allowLocalShortDesc,
		bool $forceLocalShortDesc,
		array $params,
		array $requestedPageIds,
		?int $fitLimit,
		DescriptionLookup $descriptionLookup
	) {
		$main = $this->createMock( ApiMain::class );
		$main->method( 'canApiHighLimits' )
			->willReturn( false );

		$pageSet = $this->createMock( \ApiPageSet::class );
		$pageSet->method( 'getGoodTitles' )
			->willReturn( $this->makeTitles( $requestedPageIds ) );

		$result = $this->createMock( \ApiResult::class );
		$result->method( 'addValue' )
			->willReturnCallback( function ( $path, $name, $value ) use ( $fitLimit ) {
				static $fitCount = 0;
				if ( $name === 'description' ) {
					$fitCount++;
				}
				if ( $fitLimit && $fitCount > $fitLimit ) {
					return false;
				}
				$this->assertIsArray( $path );
				$this->assertSame( 'query', $path[0] );
				$this->assertSame( 'pages', $path[1] );
				$this->resultData[$path[2]][$name] = $value;
				return true;
			} );

		$module = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getParameter', 'getPageSet', 'getMain',
							'setContinueEnumParameter', 'getResult' ] )
			->getMock();
		$modulePrivate = TestingAccessWrapper::newFromObject( $module );
		$modulePrivate->allowLocalShortDesc = $allowLocalShortDesc;
		$modulePrivate->forceLocalShortDesc = $forceLocalShortDesc;
		$modulePrivate->descriptionLookup = $descriptionLookup;
		$module->method( 'getParameter' )
			->willReturnCallback( function ( $name ) use ( $params ) {
				$finalParams = $params + [
					'continue' => 0,
					'prefersource' => 'local',
				];
				$this->assertArrayHasKey( $name, $finalParams );
				return $finalParams[$name];
			} );
		$module->method( 'getPageSet' )
			->willReturn( $pageSet );
		$module->method( 'getMain' )
			->willReturn( $main );
		$module->method( 'setContinueEnumParameter' )
			->with( 'continue', $this->anything() )
			->willReturnCallback( function ( $_, $continue ) {
				$this->continueEnumParameter = $continue;
			} );
		$module->method( 'getResult' )
			->willReturn( $result );

		return $module;
	}

	/**
	 * @param int[] $requestedPageIds
	 *
	 * @return Title[] page id => Title
	 */
	private function makeTitles( $requestedPageIds ) {
		$en = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' );
		return array_map( function ( $pageId ) use ( $en ) {
			$title = $this->createMock( Title::class );
			$title->method( 'getArticleID' )
				->willReturn( $pageId );
			$title->method( 'getPageLanguage' )
				->willReturn( $en );
			return $title;
		}, array_combine( $requestedPageIds, $requestedPageIds ) );
	}

}
