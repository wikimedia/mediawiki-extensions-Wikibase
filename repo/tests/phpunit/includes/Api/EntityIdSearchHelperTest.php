<?php

namespace Wikibase\Repo\Tests\Api;

use InvalidArgumentException;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\DispatchingEntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntityIdSearchHelper;

/**
 * @covers Wikibase\Repo\Api\EntityIdSearchHelper
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityIdSearchHelperTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	const EXISTING_LOCAL_ITEM = 'Q111';
	const FOREIGN_REPO_PREFIX = 'foreign';
	const EXISTING_FOREIGN_ITEM = 'foreign:Q2';
	const EXISTING_FOREIGN_ITEM_WITHOUT_REPOSITORY_PREFIX = 'Q2';
	const DEFAULT_LANGUAGE = 'pt';
	const DEFAULT_LABEL = 'ptLabel';
	const DEFAULT_DESCRIPTION = 'ptDescription';

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

		return new EntityIdSearchHelper(
			$entityLookup,
			new ItemIdParser(),
			$this->getMockLabelDescriptionLookup(),
			$entityTypeToRepositoryMapping
		);
	}

	public function provideTestGetRankedSearchResults() {
		$existingLocalItemResult = new TermSearchResult(
			new Term( 'qid', self::EXISTING_LOCAL_ITEM ),
			'entityId',
			new ItemId( self::EXISTING_LOCAL_ITEM ),
			new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_LABEL ),
			new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_DESCRIPTION )
		);

		$existingForeignItemResult = new TermSearchResult(
			new Term( 'qid', self::EXISTING_FOREIGN_ITEM ),
			'entityId',
			new ItemId( self::EXISTING_FOREIGN_ITEM ),
			new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_LABEL ),
			new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_DESCRIPTION )
		);

		$defaultLimit = 10;

		return [
			'No exact match' => [
				'Q999',
				$defaultLimit,
				[],
			],
			'Exact EntityId match' => [
				self::EXISTING_LOCAL_ITEM,
				$defaultLimit,
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult ],
			],
			'Exact EntityId match in foreign repository' => [
				self::EXISTING_FOREIGN_ITEM,
				$defaultLimit,
				[ self::EXISTING_FOREIGN_ITEM => $existingForeignItemResult ],
			],
			'EntityID plus term matches' => [
				self::EXISTING_LOCAL_ITEM,
				$defaultLimit,
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult ],
			],
			'Trimming' => [
				' ' . self::EXISTING_LOCAL_ITEM . ' ',
				$defaultLimit,
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult ],
			],
			'Brackets are removed' => [
				'(' . self::EXISTING_LOCAL_ITEM . ')',
				$defaultLimit,
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult ],
			],
			'URL prefixes are removed' => [
				'http://example.com/' . self::EXISTING_LOCAL_ITEM,
				$defaultLimit,
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult ],
			],
			'Single characters are ignored' => [
				'w/' . self::EXISTING_LOCAL_ITEM . '/w',
				$defaultLimit,
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult ],
			],
			'EntityID extraction' => [
				'[id:' . self::EXISTING_LOCAL_ITEM . ']',
				$defaultLimit,
				[ self::EXISTING_LOCAL_ITEM => $existingLocalItemResult ],
			],
		];
	}

	/**
	 * @dataProvider provideTestGetRankedSearchResults
	 */
	public function testGetRankedSearchResults( $search, $limit, array $expected ) {
		$entitySearchHelper = $this->newEntitySearchHelper();

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

		$entitySearchHelper = $this->newEntitySearchHelper(
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

		$this->newEntitySearchHelper( [ 'item' => [ '', 'foreign' ] ] );
	}

}
