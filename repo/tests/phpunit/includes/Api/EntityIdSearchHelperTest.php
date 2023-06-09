<?php

namespace Wikibase\Repo\Tests\Api;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntityIdSearchHelper;

/**
 * @covers \Wikibase\Repo\Api\EntityIdSearchHelper
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityIdSearchHelperTest extends \PHPUnit\Framework\TestCase {

	private const EXISTING_ITEM = 'Q111';
	private const DEFAULT_LANGUAGE = 'pt';
	private const DEFAULT_LABEL = 'ptLabel';
	private const DEFAULT_DESCRIPTION = 'ptDescription';

	/**
	 * Get a lookup that always returns a pt label and description
	 *
	 * @return LabelDescriptionLookup
	 */
	private function getMockLabelDescriptionLookup() {
		$mock = $this->createMock( LabelDescriptionLookup::class );

		$mock->method( 'getLabel' )
			->willReturn( new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_LABEL ) );
		$mock->method( 'getDescription' )
			->willReturn( new Term( self::DEFAULT_LANGUAGE, self::DEFAULT_DESCRIPTION ) );
		return $mock;
	}

	private function newEntitySearchHelper(
		array $entityTypeToRepositoryMapping = []
	) {
		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity( new Item( new ItemId( self::EXISTING_ITEM ) ) );

		return new EntityIdSearchHelper(
			$entityLookup,
			new ItemIdParser(),
			$this->getMockLabelDescriptionLookup(),
			$entityTypeToRepositoryMapping
		);
	}

	public static function provideTestGetRankedSearchResults() {
		$existingLocalItemResult = new TermSearchResult(
			new Term( 'qid', self::EXISTING_ITEM ),
			'entityId',
			new ItemId( self::EXISTING_ITEM ),
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
				self::EXISTING_ITEM,
				$defaultLimit,
				[ self::EXISTING_ITEM => $existingLocalItemResult ],
			],
			'EntityID plus term matches' => [
				self::EXISTING_ITEM,
				$defaultLimit,
				[ self::EXISTING_ITEM => $existingLocalItemResult ],
			],
			'Trimming' => [
				' ' . self::EXISTING_ITEM . ' ',
				$defaultLimit,
				[ self::EXISTING_ITEM => $existingLocalItemResult ],
			],
			'Brackets are removed' => [
				'(' . self::EXISTING_ITEM . ')',
				$defaultLimit,
				[ self::EXISTING_ITEM => $existingLocalItemResult ],
			],
			'URL prefixes are removed' => [
				'http://example.com/' . self::EXISTING_ITEM,
				$defaultLimit,
				[ self::EXISTING_ITEM => $existingLocalItemResult ],
			],
			'Single characters are ignored' => [
				'w/' . self::EXISTING_ITEM . '/w',
				$defaultLimit,
				[ self::EXISTING_ITEM => $existingLocalItemResult ],
			],
			'EntityID extraction' => [
				'[id:' . self::EXISTING_ITEM . ']',
				$defaultLimit,
				[ self::EXISTING_ITEM => $existingLocalItemResult ],
			],
			'Case insensitive' => [
				strtolower( self::EXISTING_ITEM ),
				$defaultLimit,
				[ self::EXISTING_ITEM => $existingLocalItemResult ],
			],
		];
	}

	/**
	 * @dataProvider provideTestGetRankedSearchResults
	 */
	public function testGetRankedSearchResults( $search, $limit, array $expected ) {
		$entitySearchHelper = $this->newEntitySearchHelper( [ 'item' => [ '' ] ] );

		$results = $entitySearchHelper->getRankedSearchResults( $search, 'en', 'item', $limit, false, null );
		$this->assertEquals( $expected, $results );
	}

	public function testGivenEntityTypeDefinedInMultipleRepos_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );

		$this->newEntitySearchHelper( [ 'item' => [ '', 'foreign' ] ] );
	}

}
