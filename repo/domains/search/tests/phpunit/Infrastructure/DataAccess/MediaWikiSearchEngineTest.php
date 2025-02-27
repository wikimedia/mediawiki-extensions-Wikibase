<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\DataAccess;

use Elastica\Result;
use Generator;
use MediaWiki\Context\DerivativeContext;
use MediaWiki\Context\RequestContext;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Status\Status;
use MockSearchResultSet;
use MutableContext;
use PHPUnit\Framework\TestCase;
use SearchEngine;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\MediaWikiSearchEngine;
use Wikibase\Search\Elastic\EntityResult;

/**
 * @covers \Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\MediaWikiSearchEngine
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiSearchEngineTest extends TestCase {

	private const RESULT1_ITEM_ID = 'Q1';
	private const RESULT1_LABEL = 'Label one';
	private const RESULT1_DESCRIPTION = 'Description one';
	private const RESULT2_ITEM_ID = 'Q2';
	private const RESULT2_LABEL = 'Label two';
	private const RESULT2_DESCRIPTION = 'Description two';
	private SearchEngine $searchEngine;
	private MutableContext $requestContext;

	public static function setUpBeforeClass(): void {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseCirrusSearch' ) ) {
			self::markTestSkipped( 'CirrusSearch needs to be enabled to run this test' );
		}
	}

	protected function setUp(): void {
		parent::setUp();
		$this->searchEngine = $this->createStub( SearchEngine::class );
		$this->requestContext = new DerivativeContext( RequestContext::getMain() );
	}

	/**
	 * @dataProvider searchEngineResultProvider
	 *
	 * @param Status|ItemSearchResult $result
	 */
	public function testSearchItemByLabel( $result ): void {
		$searchTerm = 'Label';
		$languageCode = 'en';

		$this->searchEngine = $this->createMock( SearchEngine::class );
		$this->searchEngine->expects( $this->once() )
			->method( 'searchText' )
			->with( $searchTerm )
			->willReturn( $result );

		$this->requestContext = $this->createMock( MutableContext::class );
		$this->requestContext->expects( $this->once() )
			->method( 'setLanguage' )
			->with( $languageCode );

		$this->assertEquals(
			new ItemSearchResults(
				new ItemSearchResult(
					new ItemId( self::RESULT1_ITEM_ID ),
					new Label( 'en', self::RESULT1_LABEL ),
					new Description( 'en', self::RESULT1_DESCRIPTION )
				),
				new ItemSearchResult(
					new ItemId( self::RESULT2_ITEM_ID ),
					new Label( 'en', self::RESULT2_LABEL ),
					new Description( 'en', self::RESULT2_DESCRIPTION )
				),
			),
			$this->newEngine()->searchItemByLabel( $searchTerm, $languageCode )
		);
	}

	public function searchEngineResultProvider(): Generator {
		$resultSet = new MockSearchResultSet( [
			$this->fakeEntityResult( self::RESULT1_ITEM_ID, self::RESULT1_LABEL, self::RESULT1_DESCRIPTION ),
			$this->fakeEntityResult( self::RESULT2_ITEM_ID, self::RESULT2_LABEL, self::RESULT2_DESCRIPTION ),
		] );

		yield 'Status' => [ Status::newGood( $resultSet ) ];
		yield 'SearchResultSet' => [ $resultSet ];
	}

	/**
	 * @dataProvider failedSearchResultProvider
	 */
	public function testGivenSearchFails_returnsNoResults( ?Status $result ): void {
		$this->searchEngine = $this->createMock( SearchEngine::class );
		$this->searchEngine->expects( $this->once() )
			->method( 'searchText' )
			->willReturn( $result );

		$this->assertEquals(
			new ItemSearchResults(),
			$this->newEngine()->searchItemByLabel( 'potato', 'en' )
		);
	}

	public function failedSearchResultProvider(): Generator {
		yield 'bad Status' => [ Status::newFatal( 'search failed' ) ];
		yield 'null' => [ null ];
	}

	private function newEngine(): MediaWikiSearchEngine {
		$namespaceLoookup = $this->createStub( EntityNamespaceLookup::class );
		$namespaceLoookup->method( 'getEntityNamespace' )->willReturn( 0 );
		return new MediaWikiSearchEngine(
			$this->searchEngine,
			$namespaceLoookup,
			$this->requestContext
		);
	}

	private function fakeEntityResult( string $itemId, string $enLabel, string $enDescription ): EntityResult {
		return new EntityResult(
			'en',
			new TermLanguageFallbackChain( [], new StaticContentLanguages( [ 'en' ] ) ),
			new Result( [
				'fields' => [ 'title' => $itemId, 'namespace' => '0' ],
				'_source' => [ 'labels' => [ 'en' => $enLabel ], 'descriptions' => [ 'en' => $enDescription ] ],
			] )
		);
	}
}
