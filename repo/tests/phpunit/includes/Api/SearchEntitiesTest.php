<?php

namespace Wikibase\Test\Repo\Api;

use ApiMain;
use FauxRequest;
use PHPUnit_Framework_TestCase;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Api\SearchEntities;
use Wikibase\Lib\Interactors\TermSearchResult;

/**
 * @covers Wikibase\Repo\Api\SearchEntities
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @group medium
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Daniel Kinzler
 */
class SearchEntitiesTest extends PHPUnit_Framework_TestCase {

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
		$titleLookup->expects( $this->any() )->method( 'getTitleForId' )
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
			array( 'de', 'de-ch', 'en', 'ii', 'nn', 'ru', 'zh-cn' )
		);
	}

	/**
	 * @param array $params
	 * @param TermSearchResult[] $returnResults
	 *
	 * @return EntitySearchHelper
	 */
	private function getMockEntitySearchHelper( array $params, array $returnResults = array() ) {
		// defaults from SearchEntities
		$params = array_merge( array(
			'strictlanguage' => false,
			'type' => 'item',
			'limit' => 7,
			'continue' => 0
		), $params );

		$mock = $this->getMockBuilder( EntitySearchHelper::class )
			->disableOriginalConstructor()
			->getMock();
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
	 * @param array $params
	 * @param EntitySearchHelper|null $entitySearchHelper
	 *
	 * @return array[]
	 */
	private function callApiModule( array $params, EntitySearchHelper $entitySearchHelper = null ) {
		$module = new SearchEntities(
			$this->getApiMain( $params ),
			'wbsearchentities'
		);

		if ( $entitySearchHelper == null ) {
			$entitySearchHelper = $this->getMockEntitySearchHelper( $params );
		}

		$module->setServices(
			$entitySearchHelper,
			$this->getMockTitleLookup(),
			$this->getContentLanguages(),
			array( 'item', 'property' ),
			'concept:'
		);

		$module->execute();

		$result = $module->getResult();
		return $result->getResultData( null, array(
			'BC' => array(),
			'Types' => array(),
			'Strip' => 'all',
		) );
	}

	public function testSearchStrictLanguage_passedToSearchInteractor() {
		$params = array(
			'action' => 'wbsearchentities',
			'search' => 'Foo',
			'type' => 'item',
			'language' => 'de-ch',
			'strictlanguage' => true
		);

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

		$q111Result = array(
			'id' => 'Q111',
			'concepturi' => 'concept:Q111',
			'url' => 'http://fullTitleUrl',
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'label' => 'ptLabel',
			'description' => 'ptDescription',
			'aliases' => array( 'Q111' ),
			'match' => array(
				'type' => 'entityId',
				'text' => 'Q111',
			),
		);

		$q222Result = array(
			'id' => 'Q222',
			'concepturi' => 'concept:Q222',
			'url' => 'http://fullTitleUrl',
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'label' => 'FooHeHe',
			'description' => 'FooHeHe en description',
			'aliases' => array( 'Fooooo' ),
			'match' => array(
				'type' => 'label',
				'language' => 'en-gb',
				'text' => 'Fooooo',
			),
		);

		$q333Result = array(
			'id' => 'Q333',
			'concepturi' => 'concept:Q333',
			'url' => 'http://fullTitleUrl',
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'label' => 'ADisplayLabel',
			'aliases' => array( 'AMatchedTerm' ),
			'match' => array(
				'type' => 'alias',
				'language' => 'de',
				'text' => 'AMatchedTerm',
			),
		);

		return array(
			'No exact match' => array(
				array( 'search' => 'Q999' ),
				array(),
				array(),
			),
			'Exact EntityId match' => array(
				array( 'search' => 'Q111' ),
				array( $q111Match ),
				array( $q111Result ),
			),
			'Multiple Results' => array(
				array(),
				array( $q222Match, $q333Match ),
				array( $q222Result, $q333Result ),
			),
			'Multiple Results (limited)' => array(
				array( 'limit' => 1 ),
				array( $q222Match, $q333Match ),
				array( $q222Result ),
			),
			'Multiple Results (limited-continue)' => array(
				array( 'limit' => 1, 'continue' => 1 ),
				array( $q222Match, $q333Match ),
				array( $q333Result ),
			),
		);
	}

	/**
	 * @dataProvider provideTestSearchEntities
	 */
	public function testSearchEntities( array $overrideParams, array $interactorReturn, array $expected ) {
		$params = array_merge( array(
			'action' => 'wbsearchentities',
			'search' => 'Foo',
			'type' => 'item',
			'language' => 'en'
		), $overrideParams );

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
			$this->assertArrayHasKey( 'id', $searchresult );
			$this->assertArrayHasKey( 'concepturi', $searchresult );
			$this->assertArrayHasKey( 'url', $searchresult );
			$this->assertArrayHasKey( 'title', $searchresult );
			$this->assertArrayHasKey( 'pageid', $searchresult );
		}
	}

}
