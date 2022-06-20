<?php

namespace Wikibase\Repo\Tests\Api;

use PHPUnit\Framework\MockObject\MockObject;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\ConfigurableTermSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchOptions;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\TermIndexEntry;
use Wikibase\Repo\Api\EntityTermSearchHelper;

/**
 * @covers \Wikibase\Repo\Api\EntityTermSearchHelper
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityTermSearchHelperTest extends \PHPUnit\Framework\TestCase {

	private const EXISTING_LOCAL_ITEM = 'Q111';

	/**
	 * @param string $search
	 * @param string $language
	 * @param string $type
	 * @param TermSearchResult[] $returnResults
	 *
	 * @return ConfigurableTermSearchInteractor|MockObject
	 */
	private function getMockSearchInteractor(
		$search,
		$language,
		$type,
		array $returnResults = []
	) {
		$mock = $this->createMock( ConfigurableTermSearchInteractor::class );
		$mock->expects( $this->atLeastOnce() )
			->method( 'searchForEntities' )
			->with(
				$search,
				$language,
				$type,
				[ TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS ]
			)
			->willReturn( $returnResults );
		return $mock;
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

		$entitySearchHelper = new EntityTermSearchHelper( $searchInteractor );
		$entitySearchHelper->getRankedSearchResults( 'Foo', 'de-ch', 'item', 10, $strictLanguage, null );
	}

	public function provideTestGetRankedSearchResults() {
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

		$defaultLimit = 10;
		$emptyInteractorResult = [];

		return [
			'No exact match' => [
				'QQQQQQQQQQQQ',
				$defaultLimit,
				$emptyInteractorResult,
				[],
			],
			'Term match (looks like ID)' => [
				self::EXISTING_LOCAL_ITEM,
				$defaultLimit,
				[ $q222Result ],
				[ 'Q222' => $q222Result ],
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
		$entitySearchHelper = new EntityTermSearchHelper( $searchInteractor );

		$results = $entitySearchHelper->getRankedSearchResults( $search, 'en', 'item', $limit, false, null );
		$this->assertEquals( $expected, $results );
	}

}
