<?php

namespace Wikibase\Repo\Tests\Api;

use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\ConfigurableTermSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchOptions;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntityTermSearchHelper;
use Wikibase\TermIndexEntry;

/**
 * @covers Wikibase\Repo\Api\EntityTermSearchHelper
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityTermSearchHelperTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	const EXISTING_LOCAL_ITEM = 'Q111';
	const DEFAULT_LANGUAGE = 'pt';
	const DEFAULT_LABEL = 'ptLabel';
	const DEFAULT_DESCRIPTION = 'ptDescription';

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

	private function newEntitySearchHelper( ConfigurableTermSearchInteractor $searchInteractor ) {
		return new EntityTermSearchHelper( $searchInteractor );
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
		$entitySearchHelper = $this->newEntitySearchHelper( $searchInteractor );

		$results = $entitySearchHelper->getRankedSearchResults( $search, 'en', 'item', $limit, false );
		$this->assertEquals( $expected, $results );
	}

}
