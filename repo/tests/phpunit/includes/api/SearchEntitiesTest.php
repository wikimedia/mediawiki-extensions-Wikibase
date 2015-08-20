<?php

namespace Wikibase\Test\Repo\Api;

use ApiMain;
use FauxRequest;
use PHPUnit_Framework_TestCase;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\BasicEntityIdParser;
use Wikibase\DataModel\Services\Lookup\LanguageLabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Api\SearchEntities;
use Wikibase\Repo\Interactors\TermIndexSearchInteractor;
use Wikibase\Repo\Interactors\TermSearchInteractor;
use Wikibase\Repo\Interactors\TermSearchResult;
use Wikibase\TermIndexEntry;
use Wikibase\Test\MockTermIndex;

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
 * @licence GNU GPL v2+
 * @author Adam Shorland
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
	 * @return EntityTitleLookup|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockTitleLookup() {
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$testCase = $this;
		$titleLookup->expects( $this->any() )->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) use ( $testCase ) {
				if ( $id->getSerialization() === 'Q111' ) {
					return $testCase->getMockTitle( true );
				} else {
					return $testCase->getMockTitle( false );
				}
			} ) );
		return $titleLookup;
	}

	/**
	 * @param bool $exists
	 *
	 * @return Title|\PHPUnit_Framework_MockObject_MockObject
	 */
	public function getMockTitle( $exists ) {
		$mock = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( $exists ) );
		$mock->expects( $this->any() )
			->method( 'getFullUrl' )
			->will( $this->returnValue( 'http://fullTitleUrl' ) );
		return $mock;
	}

	/**
	 * @return ContentLanguages|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockContentLanguages() {
		$contentLanguages = $this->getMock( 'Wikibase\Lib\ContentLanguages' );
		$contentLanguages->expects( $this->any() )->method( 'getLanguages' )
			->will( $this->returnValue( array( 'de', 'de-ch', 'en', 'ii', 'nn', 'ru', 'zh-cn' ) ) );
		return $contentLanguages;
	}

	/**
	 * @param array $params
	 * @param TermSearchResult[] $returnResults
	 *
	 * @return TermIndexSearchInteractor|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockSearchInteractor( array $params, array $returnResults = array() ) {
		$mock = $this->getMockBuilder( 'Wikibase\Repo\Interactors\TermIndexSearchInteractor' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->atLeastOnce() )
			->method( 'searchForEntities' )
			->with(
				$this->equalTo( $params['search'] ),
				$this->equalTo( $params['language'] ),
				$this->equalTo( $params['type'] ),
				$this->equalTo( array( TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS ) )
			)
			->will( $this->returnValue( $returnResults ) );
		return $mock;
	}

	/**
	 * Get a lookup that always returns a pt label and description suffixed by the entity ID
	 *
	 * @return LanguageLabelDescriptionLookup
	 */
	private function getMockLabelDescriptionLookup() {
		$mock = $this->getMockBuilder( 'Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup' )
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

	private function getMockTermIndex() {
		return new MockTermIndex(
			array()
		);
	}

	/**
	 * @param array $params
	 * @param TermSearchInteractor|null $searchInteractor
	 *
	 * @return array[]
	 */
	private function callApiModule( array $params, $searchInteractor = null ) {
		$module = new SearchEntities(
			$this->getApiMain( $params ),
			'wbsearchentities'
		);

		if ( $searchInteractor == null ) {
			$searchInteractor = $this->getMockSearchInteractor( $params );
		}

		$module->setServices(
			$this->getMockTitleLookup(),
			new BasicEntityIdParser(),
			array( 'item', 'property' ),
			$this->getMockContentLanguages(),
			$searchInteractor,
			$this->getMockTermIndex(),
			$this->getMockLabelDescriptionLookup()
		);

		$module->execute();

		$result = $module->getResult();
		return $result->getResultData( null, array(
			'BC' => array(),
			'Types' => array(),
			'Strip' => 'all',
		) );
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
		$params = array(
			'action' => 'wbsearchentities',
			'search' => 'Foo',
			'type' => 'item',
			'language' => 'de-ch',
		);
		if ( $boolean ) {
			$params['strictlanguage'] = true;
		}

		$searchInteractor = $this->getMockSearchInteractor( $params );
		$searchInteractor->expects( $this->atLeastOnce() )
			->method( 'setUseLanguageFallback' )
			->with( $this->equalTo( !$boolean ) );

		$this->callApiModule( $params, $searchInteractor );
	}

	public function provideTestSearchEntities() {
		$multipleInteractorReturnValues = array(
			new TermSearchResult(
				new Term( 'en-gb', 'Fooooo' ),
				'label',
				new ItemId( 'Q222' ),
				new Term( 'en-gb', 'FooHeHe' ),
				new Term( 'en', 'FooHeHe en description' )
			),
			new TermSearchResult(
				new Term( 'de', 'AMatchedTerm' ),
				'alias',
				new ItemId( 'Q333' ),
				new Term( 'fr', 'ADisplayLabel' )
			),
		);
		$q222Result = array(
			'id' => 'Q222',
			'url' => 'http://fullTitleUrl',
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
			'url' => 'http://fullTitleUrl',
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
				array(),
				array(
					array(
						'id' => 'Q111',
						'url' => 'http://fullTitleUrl',
						'label' => 'ptLabel',
						'description' => 'ptDescription',
						'aliases' => array( 'Q111' ),
						'match' => array(
							'type' => 'entityId',
							'text' => 'Q111',
						),
					),
				),
			),
			'Multiple Results' => array(
				array(),
				$multipleInteractorReturnValues,
				array( $q222Result, $q333Result ),
			),
			'Multiple Results (limited)' => array(
				array( 'limit' => 1 ),
				$multipleInteractorReturnValues,
				array( $q222Result ),
			),
			'Multiple Results (limited-continue)' => array(
				array( 'limit' => 1, 'continue' => 1 ),
				$multipleInteractorReturnValues,
				array( $q333Result ),
			),
		);
	}

	/**
	 * @dataProvider provideTestSearchEntities
	 */
	public function testSearchEntities( array $overrideParams, array $interactorReturn, array $expected ) {
		$params = array(
			'action' => 'wbsearchentities',
			'search' => 'Foo',
			'type' => 'item',
			'language' => 'en'
		);
		foreach ( $overrideParams as $key => $param ) {
			$params[$key] = $param;
		}

		$searchInteractor = $this->getMockSearchInteractor( $params, $interactorReturn );

		$result = $this->callApiModule( $params, $searchInteractor );

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
			$this->assertArrayHasKey( 'url', $searchresult );
		}
	}

}
