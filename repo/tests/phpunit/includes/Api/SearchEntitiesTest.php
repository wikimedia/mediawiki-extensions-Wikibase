<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use FauxRequest;
use PHPUnit4And6Compat;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Api\SearchEntities;

/**
 * @covers Wikibase\Repo\Api\SearchEntities
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Daniel Kinzler
 */
class SearchEntitiesTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @param array $params
	 *
	 * @return ApiMain
	 */
	private function getApiMain( array $params ) {
		$context = new RequestContext();
		$context->setLanguage( 'en-ca' );
		$context->setRequest( new FauxRequest( $params, true ) );
		$main = new ApiMain( $context );
		return $main;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockTitleLookup() {
		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnValue( $this->getMockTitle() ) );

		return $titleLookup;
	}

	/**
	 * @return Title
	 */
	public function getMockTitle() {
		$mock = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getFullURL' )
			->will( $this->returnValue( 'http://fullTitleUrl' ) );
		$mock->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( 'Prefixed:Title' ) );
		$mock->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 42 ) );

		return $mock;
	}

	/**
	 * @return ContentLanguages
	 */
	private function getContentLanguages() {
		return new StaticContentLanguages(
			[ 'de', 'de-ch', 'en', 'ii', 'nn', 'ru', 'zh-cn' ]
		);
	}

	/**
	 * @param array $params
	 * @param TermSearchResult[] $returnResults
	 *
	 * @return EntitySearchHelper
	 */
	private function getMockEntitySearchHelper( array $params, array $returnResults = [] ) {
		// defaults from SearchEntities
		$params = array_merge( [
			'strictlanguage' => false,
			'type' => 'item',
			'limit' => 7,
			'continue' => 0
		], $params );

		$mock = $this->getMock( EntitySearchHelper::class );
		$mock->expects( $this->atLeastOnce() )
			->method( 'getRankedSearchResults' )
			->with(
				$this->equalTo( $params['search'] ),
				$this->equalTo( $params['language'] ),
				$this->equalTo( $params['type'] ),
				$this->equalTo( $params['continue'] + $params['limit'] + 1 ),
				$this->equalTo( $params['strictlanguage'] )
			)
			->will( $this->returnValue( $returnResults ) );

		return $mock;
	}

	/**
	 * @return PropertyDataTypeLookup
	 */
	private function getMockPropertyDataTypeLookup() {
		$mock = $this->getMock( PropertyDataTypeLookup::class );
		$mock->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->willReturn( 'PropertyDataType' );

		return $mock;
	}

	/**
	 * @param array $params
	 * @param EntitySearchHelper|null $entitySearchHelper
	 *
	 * @return array[]
	 */
	private function callApiModule( array $params, EntitySearchHelper $entitySearchHelper = null ) {
		$module = new SearchEntities(
			$this->getApiMain( $params ),
			'wbsearchentities',
			$entitySearchHelper ?: $this->getMockEntitySearchHelper( $params ),
			$this->getMockTitleLookup(),
			$this->getMockPropertyDataTypeLookup(),
			$this->getContentLanguages(),
			[ 'item', 'property' ],
			[ '' => 'http://acme.test/concept/', 'foreign' => 'http://foreign.wiki/concept/' ]
		);

		$module->execute();

		$result = $module->getResult();
		return $result->getResultData( null, [
			'BC' => [],
			'Types' => [],
			'Strip' => 'all',
		] );
	}

	public function testSearchStrictLanguage_passedToSearchInteractor() {
		$params = [
			'action' => 'wbsearchentities',
			'search' => 'Foo',
			'type' => 'item',
			'language' => 'de-ch',
			'strictlanguage' => true
		];

		$this->callApiModule( $params );
	}

	public function provideTestSearchEntities() {
		$q111Match = new TermSearchResult(
			new Term( 'qid', 'Q111' ),
			'entityId',
			new ItemId( 'Q111' ),
			new Term( 'pt', 'ptLabel' ),
			new Term( 'pt', 'ptDescription' )
		);

		$q222Match = new TermSearchResult(
			new Term( 'en-gb', 'Fooooo' ),
			'label',
			new ItemId( 'Q222' ),
			new Term( 'en-gb', 'FooHeHe' ),
			new Term( 'en', 'FooHeHe en description' )
		);

		$q333Match = new TermSearchResult(
			new Term( 'de', 'AMatchedTerm' ),
			'alias',
			new ItemId( 'Q333' ),
			new Term( 'fr', 'ADisplayLabel' )
		);

		$foreignItemMatch = new TermSearchResult(
			new Term( 'de', 'SomeText' ),
			'label',
			new ItemId( 'foreign:Q333' ),
			new Term( 'de', 'SomeText' )
		);

		$propertyMatch = new TermSearchResult(
			new Term( 'en', 'PropertyLabel' ),
			'label',
			new PropertyId( 'P123' ),
			new Term( 'en', 'PropertyLabel' )
		);

		$q111Result = [
			'repository' => '',
			'id' => 'Q111',
			'concepturi' => 'http://acme.test/concept/Q111',
			'url' => 'http://fullTitleUrl',
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'label' => 'ptLabel',
			'description' => 'ptDescription',
			'aliases' => [ 'Q111' ],
			'match' => [
				'type' => 'entityId',
				'text' => 'Q111',
			],
		];

		$q222Result = [
			'repository' => '',
			'id' => 'Q222',
			'concepturi' => 'http://acme.test/concept/Q222',
			'url' => 'http://fullTitleUrl',
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'label' => 'FooHeHe',
			'description' => 'FooHeHe en description',
			'aliases' => [ 'Fooooo' ],
			'match' => [
				'type' => 'label',
				'language' => 'en-gb',
				'text' => 'Fooooo',
			],
		];

		$q333Result = [
			'repository' => '',
			'id' => 'Q333',
			'concepturi' => 'http://acme.test/concept/Q333',
			'url' => 'http://fullTitleUrl',
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'label' => 'ADisplayLabel',
			'aliases' => [ 'AMatchedTerm' ],
			'match' => [
				'type' => 'alias',
				'language' => 'de',
				'text' => 'AMatchedTerm',
			],
		];

		$foreignItemResult = [
			'repository' => 'foreign',
			'id' => 'foreign:Q333',
			'concepturi' => 'http://foreign.wiki/concept/Q333',
			'url' => 'http://fullTitleUrl',
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'label' => 'SomeText',
			'match' => [
				'type' => 'label',
				'language' => 'de',
				'text' => 'SomeText',
			],
		];

		$propertyResult = [
			'repository' => '',
			'id' => 'P123',
			'concepturi' => 'http://acme.test/concept/P123',
			'url' => 'http://fullTitleUrl',
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'datatype' => 'PropertyDataType',
			'label' => 'PropertyLabel',
			'match' => [
				'type' => 'label',
				'language' => 'en',
				'text' => 'PropertyLabel',
			],
		];

		$q333ResultWithoutUrl = [
			'repository' => '',
			'id' => 'Q333',
			'concepturi' => 'http://acme.test/concept/Q333',
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'label' => 'ADisplayLabel',
			'aliases' => [ 'AMatchedTerm' ],
			'match' => [
				'type' => 'alias',
				'language' => 'de',
				'text' => 'AMatchedTerm',
			],
		];

		return [
			'No exact match' => [
				[ 'search' => 'Q999' ],
				[],
				[],
			],
			'Exact EntityId match' => [
				[ 'search' => 'Q111' ],
				[ $q111Match ],
				[ $q111Result ],
			],
			'Multiple Results' => [
				[],
				[ $q222Match, $q333Match ],
				[ $q222Result, $q333Result ],
			],
			'Multiple Results (limited)' => [
				[ 'limit' => 1 ],
				[ $q222Match, $q333Match ],
				[ $q222Result ],
			],
			'Multiple Results (limited-continue)' => [
				[ 'limit' => 1, 'continue' => 1 ],
				[ $q222Match, $q333Match ],
				[ $q333Result ],
			],
			'Foreign entity matched' => [
				[ 'search' => 'SomeText' ],
				[ $foreignItemMatch ],
				[ $foreignItemResult ],
			],
			'Property has datatype' => [
				[ 'search' => 'PropertyLabel', 'type' => 'property' ],
				[ $propertyMatch ],
				[ $propertyResult ],
			],
			'URL is omitted' => [
				[ 'search' => 'Q333', 'props' => '' ],
				[ $q333Match ],
				[ $q333ResultWithoutUrl ],
			],
		];
	}

	/**
	 * @dataProvider provideTestSearchEntities
	 */
	public function testSearchEntities( array $overrideParams, array $interactorReturn, array $expected ) {
		$params = array_merge( [
			'action' => 'wbsearchentities',
			'search' => 'Foo',
			'type' => 'item',
			'language' => 'en'
		], $overrideParams );

		$entitySearchHelper = $this->getMockEntitySearchHelper( $params, $interactorReturn );

		$result = $this->callApiModule( $params, $entitySearchHelper );

		$this->assertResultLooksGood( $result );
		$this->assertEquals( $expected, $result['search'] );
	}

	private function assertResultLooksGood( $result ) {
		$this->assertArrayHasKey( 'searchinfo', $result );
		$this->assertArrayHasKey( 'search', $result['searchinfo'] );
		$this->assertArrayHasKey( 'search', $result );

		foreach ( $result['search'] as $key => $searchresult ) {
			$this->assertInternalType( 'integer', $key );
			$this->assertArrayHasKey( 'repository', $searchresult );
			$this->assertArrayHasKey( 'id', $searchresult );
			$this->assertArrayHasKey( 'concepturi', $searchresult );
			$this->assertArrayHasKey( 'title', $searchresult );
			$this->assertArrayHasKey( 'pageid', $searchresult );
		}
	}

	public function testGivenEntityIdContainsUriUnsafeCharacters_conceptUriContainsEncodedCharacters() {
		$nyanId = $this->getMockBuilder( EntityId::class )
			->disableOriginalConstructor()
			->getMock();
		$nyanId->method( 'getLocalPart' )
			->will( $this->returnValue( '[,,_,,];3' ) );
		$nyanId->method( 'getEntityType' )
			->will( $this->returnValue( 'kitten' ) );

		$params = [
			'action' => 'wbsearchentities',
			'search' => 'nyan',
			'type' => 'kitten',
			'language' => 'en'
		];

		$match = new TermSearchResult(
			new Term( 'en', 'nyan' ),
			'label',
			$nyanId,
			new Term( 'en', 'nyan' )
		);

		$searchHelper = $this->getMockEntitySearchHelper( $params, [ $match ] );

		$module = new SearchEntities(
			$this->getApiMain( $params ),
			'wbsearchentities',
			$searchHelper,
			$this->getMockTitleLookup(),
			$this->getMockPropertyDataTypeLookup(),
			$this->getContentLanguages(),
			[ 'kitten' ],
			[ '' => 'http://acme.test/concept/', 'foreign' => 'http://foreign.wiki/concept/' ]
		);

		$module->execute();

		$result = $module->getResult()->getResultData( null, [
			'BC' => [],
			'Types' => [],
			'Strip' => 'all',
		] );

		$this->assertEquals( 'http://acme.test/concept/%5B,,_,,%5D;3', $result['search'][0]['concepturi'] );
	}

}
