<?php

namespace Wikibase\Repo\Tests\Api;

use InvalidArgumentException;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\DispatchingEntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\ConfigurableTermSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchOptions;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchTermIndex;
use Wikibase\TermIndexEntry;

/**
 * @covers Wikibase\Repo\Api\EntitySearchTermIndex
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntitySearchTermIndexTest extends \PHPUnit_Framework_TestCase {

	const EXISTING_LOCAL_ITEM = 'Q111';
	const FOREIGN_REPO_PREFIX = 'foreign';
	const EXISTING_FOREIGN_ITEM = 'foreign:Q2';
	const EXISTING_FOREIGN_ITEM_WITHOUT_REPOSITORY_PREFIX = 'Q2';
	const DEFAULT_LANGUAGE = 'pt';
	const DEFAULT_LABEL = 'ptLabel';
	const DEFAULT_DESCRIPTION = 'ptDescription';

	/**
	 * @param bool $exists
	 *
	 * @return Title
	 */
	public function getMockTitle( $exists ) {
		$mock = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( $exists ) );
		return $mock;
	}

	/**
	 * @param string $search
	 * @param string $language
	 * @param string $type
	 * @param TermSearchResult[] $returnResults
	 *
	 * @return ConfigurableTermSearchInteractor|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockSearchInteractor(
		$search,
		$language,
		$type,
		array $returnResults = []
	) {
		$mock = $this->getMock( ConfigurableTermSearchInteractor::class );
		$mock->expects( $this->atLeastOnce() )
			->method( 'searchForEntities' )
			->with(
				$this->equalTo( $search ),
				$this->equalTo( $language ),
				$this->equalTo( $type ),
				$this->equalTo( [ TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS ] )
			)
			->will( $this->returnValue( $returnResults ) );
		return $mock;
	}

	/**
	 * Get a lookup that always returns a pt label and description
	 *
	 * @return LabelDescriptionLookup
	 */
	private function getMockLabelDescriptionLookup() {
		$mock = $this->getMock( LabelDescriptionLookup::class );

		$mock->method( 'getLabel' )
			->will( $this->returnValue( new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_LABEL ) ) );
		$mock->method( 'getDescription' )
			->will(
				$this->returnValue( new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_DESCRIPTION ) )
			);
		return $mock;
	}

	private function newEntitySearchHelper(
		ConfigurableTermSearchInteractor $searchInteractor,
		array $entityTypeToRepositoryMapping = []
	) {
		$localEntityLookup = new InMemoryEntityLookup();
		$localEntityLookup->addEntity( new Item( new ItemId( self::EXISTING_LOCAL_ITEM ) ) );

		$fooEntityLookup = new InMemoryEntityLookup();
		$fooEntityLookup->addEntity( new Item( new ItemId( self::EXISTING_FOREIGN_ITEM ) ) );

		$entityLookup = new DispatchingEntityLookup(
			[
				'' => $localEntityLookup,
				self::FOREIGN_REPO_PREFIX => $fooEntityLookup,
			]
		);

		return new EntitySearchTermIndex(
			$entityLookup,
			new ItemIdParser(),
			$searchInteractor,
			$this->getMockLabelDescriptionLookup(),
			$entityTypeToRepositoryMapping
		);
	}

	public function provideStrictLanguageValues() {
		return [
			[ true ],
			[ false ],
		];
	}

	/**
	 * @dataProvider provideStrictLanguageValues
	 */
	public function testSearchStrictLanguage_passedToSearchInteractor( $strictLanguage ) {
		$searchInteractor = $this->getMockSearchInteractor( 'Foo', 'de-ch', 'item' );
		$searchInteractor->expects( $this->atLeastOnce() )
			->method( 'setTermSearchOptions' )
			->with( $this->callback(
				function ( TermSearchOptions $options ) use ( $strictLanguage ) {
					return $options->getUseLanguageFallback() !== $strictLanguage;
				}
			) );

		$entitySearchHelper = $this->newEntitySearchHelper( $searchInteractor );
		$entitySearchHelper->getRankedSearchResults( 'Foo', 'de-ch', 'item', 10, $strictLanguage );
	}

	public function provideTestGetRankedSearchResults() {
		$existingLocalItemResult = new TermSearchResult(
			new Term( 'qid', self::EXISTING_LOCAL_ITEM ),
			'entityId',
			new ItemId( self::EXISTING_LOCAL_ITEM ),
			new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_LABEL ),
			new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_DESCRIPTION )
		);

		$q222Result = new TermSearchResult(
			new Term( 'en-gb', 'Fooooo' ),
			'label',
			new ItemId( 'Q222' ),
			new Term( 'en-gb', 'FooHeHe' ),
			new Term( 'en', 'FooHeHe en description' )
		);

		$q333Result = new TermSearchResult(
			new Term( 'de', 'AMatchedTerm' ),
			'alias',
			new ItemId( 'Q333' ),
			new Term( 'fr', 'ADisplayLabel' )
		);

		$existingForeignItemResult = new TermSearchResult(
			new Term( 'qid', self::EXISTING_FOREIGN_ITEM ),
			'entityId',
			new ItemId( self::EXISTING_FOREIGN_ITEM ),
			new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_LABEL ),
			new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_DESCRIPTION )
		);

		$defaultLimit = 10;
		$emptyInteractorResult = [];

		return [
			'No exact match' => [
				'Q999',
				$defaultLimit,
				$emptyInteractorResult,
				[],
			],
			'Exact EntityId match' => [
				self::EXISTING_LOCAL_ITEM,
				$defaultLimit,
				$emptyInteractorResult,
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult ],
			],
			'Exact EntityId match in foreign repository' => [
				self::EXISTING_FOREIGN_ITEM,
				$defaultLimit,
				$emptyInteractorResult,
				[ self::EXISTING_FOREIGN_ITEM => $existingForeignItemResult ],
			],
			'EntityID plus term matches' => [
				self::EXISTING_LOCAL_ITEM,
				$defaultLimit,
				[ $q222Result ],
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult, 'Q222' => $q222Result ],
			],
			'Trimming' => [
				' ' . self::EXISTING_LOCAL_ITEM . ' ',
				$defaultLimit,
				$emptyInteractorResult,
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult ],
			],
			'Brackets are removed' => [
				'(' . self::EXISTING_LOCAL_ITEM . ')',
				$defaultLimit,
				$emptyInteractorResult,
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult ],
			],
			'URL prefixes are removed' => [
				'http://example.com/' . self::EXISTING_LOCAL_ITEM,
				$defaultLimit,
				$emptyInteractorResult,
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult ],
			],
			'Single characters are ignored' => [
				'w/' . self::EXISTING_LOCAL_ITEM . '/w',
				$defaultLimit,
				$emptyInteractorResult,
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult ],
			],
			'EntityID extraction plus term matches' => [
				'[id:' . self::EXISTING_LOCAL_ITEM . ']',
				$defaultLimit,
				[ $q222Result ],
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult, 'Q222' => $q222Result ],
			],
			'Multiple Results' => [
				'Foo',
				$defaultLimit,
				[ $q222Result, $q333Result ],
				[ 'Q222' => $q222Result, 'Q333' => $q333Result ],
			],
			'Multiple Results (limited)' => [
				'Foo',
				1,
				[ $q222Result ],
				[ 'Q222' => $q222Result ],
			],
		];
	}

	/**
	 * @dataProvider provideTestGetRankedSearchResults
	 */
	public function testGetRankedSearchResults( $search, $limit, array $interactorReturn, array $expected ) {
		$searchInteractor = $this->getMockSearchInteractor( $search, 'en', 'item', $interactorReturn );
		$entitySearchHelper = $this->newEntitySearchHelper( $searchInteractor );

		$results = $entitySearchHelper->getRankedSearchResults( $search, 'en', 'item', $limit, false );
		$this->assertEquals( $expected, $results );
	}

	public function testGivenEntityIdWithoutRepositoryPrefix_entityIsFound() {
		$expectedResults = [
			self::EXISTING_FOREIGN_ITEM => new TermSearchResult(
				new Term( 'qid', self::EXISTING_FOREIGN_ITEM ),
				'entityId',
				new ItemId( self::EXISTING_FOREIGN_ITEM ),
				new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_LABEL ),
				new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_DESCRIPTION )
			)
		];

		$mockSearchInteractor = $this->getMock( ConfigurableTermSearchInteractor::class );
		$mockSearchInteractor->method( 'searchForEntities' )
			->will( $this->returnValue( [] ) );

		$entitySearchHelper = $this->newEntitySearchHelper(
			$mockSearchInteractor,
			[ 'item' => [ [ 'foreign', 123 ] ] ]
		);

		$this->assertEquals(
			$expectedResults,
			$entitySearchHelper->getRankedSearchResults(
				self::EXISTING_FOREIGN_ITEM_WITHOUT_REPOSITORY_PREFIX,
				'en',
				'item',
				10,
				false
			)
		);
	}

	public function testGivenEntityTypeDefinedInMultipleRepos_constructorThrowsException() {
		$this->setExpectedException( InvalidArgumentException::class );

		$mockSearchInteractor = $this->getMock( ConfigurableTermSearchInteractor::class );
		$mockSearchInteractor->method( 'searchForEntities' )
			->will( $this->returnValue( [] ) );

		$this->newEntitySearchHelper( $mockSearchInteractor, [ 'item' => [ '', 'foreign' ] ] );
	}

}
