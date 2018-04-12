<?php

namespace Wikibase\Client\Tests\Api;

use ApiMain;
use Language;
use PHPUnit4And6Compat;
use PageProps;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\Client\Api\Description;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Store\EntityIdLookup;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;
use Wikimedia\ScopedCallback;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers Wikibase\Client\Api\Description
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 */
class DescriptionTest extends TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @var array[] page id => data
	 */
	private $resultData;

	/**
	 * @var int
	 */
	private $continueEnumParameter;

	public function setUp() {
		parent::setUp();
		$this->resultData = [];
		$this->continueEnumParameter = null;
	}

	/**
	 * @dataProvider provideExecute
	 * @param bool $allowLocalShortDesc
	 * @param array $params
	 * @param array $requestedPageIds
	 * @param array $localDescriptions
	 * @param array $centralDescriptions
	 * @param int $fitLimit
	 * @param array $expectedResult
	 * @param int $expectedContinue
	 */
	public function testExecute(
		$allowLocalShortDesc,
		array $params,
		array $requestedPageIds,
		array $localDescriptions,
		array $centralDescriptions,
		$fitLimit,
		array $expectedResult,
		$expectedContinue
	) {
		$scope = $this->mockPageProps( $localDescriptions );
		$idLookup = $this->getIdLookup( $centralDescriptions );
		$termIndex = $this->getTermIndex( $centralDescriptions );
		$module = $this->getModule( $allowLocalShortDesc, $params, $requestedPageIds, $fitLimit,
			$idLookup, $termIndex );
		$module->execute();
		$this->assertSame( $expectedResult, $this->resultData );
		$this->assertSame( $expectedContinue, $this->continueEnumParameter );
	}

	public function provideExecute() {
		return [
			'empty' => [
				'allow local descriptions' => false,
				'API params' => [],
				'requested page IDs' => [],
				'local descriptions by page ID' => [],
				'central descriptions by page ID' => [],
				'fit limit' => null,
				'expected results' => [],
				'expected continuation value' => null,
			],
			'local disallowed' => [
				'allow local descriptions' => false,
				'API params' => [],
				'requested page IDs' => [ 1, 2, 3, 4, 5 ],
				'local descriptions by page ID' => [ 1 => 'L1', 2 => 'L2', 4 => 'L3', 5 => 'L4' ],
				'central descriptions by page ID' => [ 3 => 'C3', 4 => 'C4', 5 => null ],
				'fit limit' => null,
				'expected results' => [
					3 => [ 'description' => 'C3', 'descriptionsource' => 'central' ],
					4 => [ 'description' => 'C4', 'descriptionsource' => 'central' ],
				],
				'expected continuation value' => null,
			],
			'prefer local' => [
				'allow local descriptions' => true,
				'API params' => [],
				'requested page IDs' => [ 1, 2, 3, 4, 5, 6 ],
				'local descriptions by page ID' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'central descriptions by page ID' => [ 2 => null, 3 => 'C3', 4 => 'C4', 5 => null ],
				'fit limit' => null,
				'expected results' => [
					1 => [ 'description' => 'L1', 'descriptionsource' => 'local' ],
					2 => [ 'description' => 'L2', 'descriptionsource' => 'local' ],
					3 => [ 'description' => 'L3', 'descriptionsource' => 'local' ],
					4 => [ 'description' => 'C4', 'descriptionsource' => 'central' ],
				],
				'expected continuation value' => null,
			],
			'prefer central' => [
				'allow local descriptions' => true,
				'API params' => [ 'prefersource' => 'central' ],
				'requested page IDs' => [ 1, 2, 3, 4, 5, 6 ],
				'local descriptions by page ID' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'central descriptions by page ID' => [ 2 => null, 3 => 'C3', 4 => 'C4', 5 => null ],
				'fit limit' => null,
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
				'API params' => [],
				'requested page IDs' => [ 1, 2, 3, 4, 5 ],
				'local descriptions by page ID' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'central descriptions by page ID' => [ 2 => 'C2', 3 => 'C3', 4 => 'C4', 5 => 'C5' ],
				'fit limit' => 2,
				'expected results' => [
					1 => [ 'description' => 'L1', 'descriptionsource' => 'local' ],
					2 => [ 'description' => 'L2', 'descriptionsource' => 'local' ],
				],
				'expected continuation value' => 2,
			],
			'continuation #2' => [
				'allow local descriptions' => true,
				'API params' => [ 'continue' => 2 ],
				'requested page IDs' => [ 1, 2, 3, 4, 5 ],
				'local descriptions by page ID' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'central descriptions by page ID' => [ 2 => 'C2', 3 => 'C3', 4 => 'C4', 5 => 'C5' ],
				'fit limit' => 2,
				'expected results' => [
					3 => [ 'description' => 'L3', 'descriptionsource' => 'local' ],
					4 => [ 'description' => 'C4', 'descriptionsource' => 'central' ],
				],
				'expected continuation value' => 4,
			],
			'continuation #3' => [
				'allow local descriptions' => true,
				'API params' => [ 'continue' => 4 ],
				'requested page IDs' => [ 1, 2, 3, 4, 5 ],
				'local descriptions by page ID' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'central descriptions by page ID' => [ 2 => 'C2', 3 => 'C3', 4 => 'C4', 5 => 'C5' ],
				'fit limit' => 2,
				'expected results' => [
					5 => [ 'description' => 'C5', 'descriptionsource' => 'central' ],
				],
				'expected continuation value' => null,
			],
			'continuation with exact fit' => [
				'allow local descriptions' => true,
				'API params' => [ 'continue' => 2 ],
				'requested page IDs' => [ 1, 2, 3, 4 ],
				'local descriptions by page ID' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'central descriptions by page ID' => [ 2 => 'C2', 3 => 'C3', 4 => 'C4' ],
				'fit limit' => 2,
				'expected results' => [
					3 => [ 'description' => 'L3', 'descriptionsource' => 'local' ],
					4 => [ 'description' => 'C4', 'descriptionsource' => 'central' ],
				],
				'expected continuation value' => null,
			],
			'limit' => [
				'allow local descriptions' => true,
				'API params' => [],
				'requested page IDs' => range( 1, 600 ),
				'local descriptions by page ID' => array_fill( 1, 600, 'local' ),
				'central descriptions by page ID' => [],
				'fit limit' => null,
				'expected results' => array_fill( 1, 500, [ 'description' => 'local',
					'descriptionsource' => 'local' ] ),
				'expected continuation value' => 500,
			],
		];
	}

	/**
	 * Create the module, mock ApiBase methods and other API dependencies, have the
	 * mock write results and continuation value into member variables of the test for inspection.
	 *
	 * @param bool $allowLocalShortDesc
	 * @param array $params API parameters for the module (unprefixed)
	 * @param array $requestedPageIds
	 * @param int|null $fitLimit
	 * @param EntityIdLookup $idLookup
	 * @param TermIndex $termIndex
	 *
	 * @return Description
	 */
	private function getModule(
		$allowLocalShortDesc,
		array $params,
		array $requestedPageIds,
		$fitLimit,
		EntityIdLookup $idLookup,
		TermIndex $termIndex
	) {
		$main = $this->getMockBuilder( ApiMain::class )
			->disableOriginalConstructor()
			->getMock();
		$main->expects( $this->any() )
			->method( 'canApiHighLimits' )
			->willReturn( false );

		$pageSet = $this->getMockBuilder( \ApiPageSet::class )
			->disableOriginalConstructor()
			->getMock();
		$pageSet->expects( $this->any() )
			->method( 'getGoodTitles' )
			->willReturn( $this->makeTitles( $requestedPageIds ) );

		$result = $this->getMockBuilder( \ApiResult::class )
			->disableOriginalConstructor()
			->getMock();
		$result->expects( $this->any() )
			->method( 'addValue' )
			->willReturnCallback( function ( $path, $name, $value ) use ( $fitLimit ) {
				static $fitCount = 0;
				if ( $name === 'description' ) {
					$fitCount++;
				}
				if ( $fitLimit && $fitCount > $fitLimit ) {
					return false;
				}
				$this->assertInternalType( 'array', $path );
				$this->assertSame( 'query', $path[0] );
				$this->assertSame( 'pages', $path[1] );
				$this->resultData[$path[2]][$name] = $value;
				return true;
			} );

		$module = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getParameter', 'getPageSet', 'getMain',
							'setContinueEnumParameter', 'getResult' ] )
			->getMock();
		$modulePrivate = TestingAccessWrapper::newFromObject( $module );
		$modulePrivate->allowLocalShortDesc = $allowLocalShortDesc;
		$modulePrivate->contentLanguage = Language::factory( 'en' );
		$modulePrivate->idLookup = $idLookup;
		$modulePrivate->termIndex = $termIndex;
		$module->expects( $this->any() )
			->method( 'getParameter' )
			->willReturnCallback( function ( $name ) use ( $params ) {
				$finalParams = $params + [
					'continue' => 0,
					'prefersource' => 'local',
				];
				$this->assertArrayHasKey( $name, $finalParams );
				return $finalParams[$name];
			} );
		$module->expects( $this->any() )
			->method( 'getPageSet' )
			->willReturn( $pageSet );
		$module->expects( $this->any() )
			->method( 'getMain' )
			->willReturn( $main );
		$module->expects( $this->any() )
			->method( 'setContinueEnumParameter' )
			->with( 'continue', $this->anything() )
			->willReturnCallback( function ( $_, $continue ) {
				$this->continueEnumParameter = $continue;
			} );
		$module->expects( $this->any() )
			->method( 'getResult' )
			->willReturn( $result );

		return $module;
	}

	/**
	 * @param int[] $requestedPageIds
	 *
	 * @return Title[] page id => Title
	 */
	private function makeTitles( $requestedPageIds ) {
		return array_map( function ( $pageId ) {
			$title = $this->getMockBuilder( Title::class )
				->disableOriginalConstructor()
				->getMock();
			$title->expects( $this->any() )
				->method( 'getArticleID' )
				->willReturn( $pageId );
			return $title;
		}, array_combine( $requestedPageIds, $requestedPageIds ) );
	}

	/**
	 * Mock page property lookup.
	 *
	 * @param array $localDescriptions page id => description
	 *
	 * @return ScopedCallback
	 */
	private function mockPageProps( array $localDescriptions ) {
		$pageProps = $this->getMockBuilder( PageProps::class )
			->disableOriginalConstructor()
			->getMock();
		$pageProps->expects( $this->any() )
			->method( 'getProperties' )
			->with( $this->anything(), 'wikibase-shortdesc' )
			->willReturnCallback( function ( $titlesByPageId ) use ( $localDescriptions ) {
				return array_filter( array_map( function ( Title $title ) use ( $localDescriptions ) {
					if ( !array_key_exists( $title->getArticleID(), $localDescriptions ) ) {
						return null;
					}
					return $localDescriptions[$title->getArticleID()];
				}, $titlesByPageId ), function ( $description ) {
					return $description !== null;
				} );
			} );
		return PageProps::overrideInstance( $pageProps );
	}

	/**
	 * Mock id lookup.
	 *
	 * To keep things simple, we just pretend each title which has a central description
	 * is linked to the entity 'Q<pageid>'.
	 *
	 * @param array $centralDescriptions page id => description
	 *   If $centralDescriptions[<pageid>] is missing, there is no linked entity;
	 *   if it is null, there is no description.
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|EntityIdLookup
	 */
	private function getIdLookup( array $centralDescriptions ) {
		$idLookup = $this->getMockBuilder( EntityIdLookup::class )
			->getMockForAbstractClass();
		$idLookup->expects( $this->any() )
			->method( 'getEntityIds' )
			->willReturnCallback( function ( $titlesByPageId ) use ( $centralDescriptions ) {
				return array_filter( array_map( function ( Title $title ) use ( $centralDescriptions ) {
					if ( !array_key_exists( $title->getArticleID(), $centralDescriptions ) ) {
						return null;
					}
					return new ItemId( ItemId::joinSerialization(
						[ 'central', '', 'Q' . $title->getArticleID() ] ) );
				}, $titlesByPageId ) );
			} );
		return $idLookup;
	}

	/**
	 * Mock term lookup.
	 *
	 * Assumes the setup used by getIdLookup() and must be called with the same $centralDescriptions
	 * array.
	 *
	 * @param array $centralDescriptions page id => description
	 *   If $centralDescriptions[<pageid>] is missing, there is no linked entity;
	 *   if it is null, there is no description.
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|TermIndex
	 */
	private function getTermIndex( array $centralDescriptions ) {
		$termIndex = $this->getMockBuilder( TermIndex::class )
			->disableOriginalConstructor()
			->getMock();
		$termIndex->expects( $this->any() )
			->method( 'getTermsOfEntities' )
			->willReturnCallback( function ( $entityIdsByPageId ) use ( $centralDescriptions ) {
				return array_values( array_filter( array_map(
					function ( EntityId $entityId ) use ( $centralDescriptions ) {
						$pageId = substr( $entityId->getLocalPart(), 1 );
						if ( $centralDescriptions[$pageId] === null ) {
							return null;
						}
						return new TermIndexEntry( [
							'termType' => TermIndexEntry::TYPE_DESCRIPTION,
							'termLanguage' => 'en',
							'termText' => $centralDescriptions[$pageId],
							'entityId' => $entityId,
						] );
					}, $entityIdsByPageId ) ) );
			} );
		return $termIndex;
	}

}
