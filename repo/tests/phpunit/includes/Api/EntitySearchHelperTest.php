<?php

namespace Wikibase\Test\Repo\Api;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Lib\Interactors\TermIndexSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\TermIndexEntry;

/**
 * @covers Wikibase\Repo\Api\EntitySearchHelper
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntitySearchHelperTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockTitleLookup() {
		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->any() )->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				if ( $id->getSerialization() === 'Q111' ) {
					return $this->getMockTitle( true );
				} else {
					return $this->getMockTitle( false );
				}
			} ) );
		return $titleLookup;
	}

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
	 * @return TermIndexSearchInteractor|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockSearchInteractor( $search, $language, $type, array $returnResults = [] ) {
		$mock = $this->getMockBuilder( TermIndexSearchInteractor::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->atLeastOnce() )
			->method( 'searchForEntities' )
			->with(
				$this->equalTo( $search ),
				$this->equalTo( $language ),
				$this->equalTo( $type ),
				$this->equalTo( array( TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS ) )
			)
			->will( $this->returnValue( $returnResults ) );
		return $mock;
	}

	/**
	 * Get a lookup that always returns a pt label and description suffixed by the entity ID
	 *
	 * @return LabelDescriptionLookup
	 */
	private function getMockLabelDescriptionLookup() {
		$mock = $this->getMockBuilder( LabelDescriptionLookup::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( new Term( 'pt', 'ptLabel' ) ) );
		$mock->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue( new Term( 'pt', 'ptDescription' ) ) );
		return $mock;
	}

	private function newEntitySearchHelper( TermIndexSearchInteractor $searchInteractor ) {
		return new EntitySearchHelper(
			$this->getMockTitleLookup(),
			new BasicEntityIdParser(),
			$searchInteractor,
			$this->getMockLabelDescriptionLookup()
		);
	}

	public function provideBooleanValues() {
		return array(
			array( true ),
			array( false ),
		);
	}

	/**
	 * @dataProvider provideBooleanValues
	 */
	public function testSearchStrictLanguage_passedToSearchInteractor( $boolean ) {
		$searchInteractor = $this->getMockSearchInteractor( 'Foo', 'de-ch', 'item' );
		$searchInteractor->expects( $this->atLeastOnce() )
			->method( 'setUseLanguageFallback' )
			->with( $this->equalTo( !$boolean ) );

		$entitySearchHelper = $this->newEntitySearchHelper( $searchInteractor );
		$entitySearchHelper->getRankedSearchResults( 'Foo', 'de-ch', 'item', 10, $boolean );
	}

	public function provideTestGetRankedSearchResults() {
		$q111Result = new TermSearchResult(
			new Term( 'qid', 'Q111' ),
			'entityId',
			new ItemId( 'Q111' ),
			new Term( 'pt', 'ptLabel' ),
			new Term( 'pt', 'ptDescription' )
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

		return array(
			'No exact match' => array(
				'Q999', 10, [], []
			),
			'Exact EntityId match' => array(
				'Q111', 10, [], array( 'Q111' => $q111Result )
			),
			'Multiple Results' => array(
				'Foo', 10, array( $q222Result, $q333Result ), array( 'Q222' => $q222Result, 'Q333' => $q333Result )
			),
			'Multiple Results (limited)' => array(
				'Foo', 1, array( $q222Result ), array( 'Q222' => $q222Result )
			),
		);
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
