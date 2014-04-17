<?php

namespace Wikibase\Test\Api;

use ApiBase;
use ApiMain;
use ApiTestCase;
use FauxRequest;
use MediaWikiTestCase;
use RequestContext;
use TestSites;
use TestUser;
use Title;
use UsageException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevisionLookup;
use Wikibase\EntityTitleLookup;
use Wikibase\SiteLinkCache;

/**
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author Adam Shorland
 */
abstract class NewWikibaseApiTestCase extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		static $isSetup = false;

		ApiTestCase::$users['wbeditor'] = new TestUser(
			'Apitesteditor',
			'Api Test Editor',
			'api_test_editor@example.com',
			array( 'wbeditor' )
		);

		$this->setMwGlobals( 'wgUser', self::$users['wbeditor']->user );

		if ( !$isSetup ) {
			//TODO remove me once everything that needs this is overridden
			TestSites::insertIntoDb();
			$isSetup = true;
		}
	}

	/**
	 * @since 0.5
	 *
	 * @param array $params
	 *
	 * @return array api request result
	 */
	public function doApiRequest( $params ) {
		$module = $this->getModule( $params );
		$module->execute();
		return $module->getResultData();
	}

	/**
	 * @since 0.5
	 *
	 * Do the test for exceptions from Api queries.
	 * @param $params array of params for the api query
	 * @param $exception array details of the exception to expect (type,code,message)
	 */
	public function doTestQueryExceptions( $params, $exception ) {
		try {
			$this->doApiRequest( $params );
			$this->fail( "Failed to throw UsageException" );

		} catch( UsageException $e ) {
			if ( array_key_exists( 'type', $exception ) ) {
				$this->assertInstanceOf( $exception['type'], $e );
			}
			if ( array_key_exists( 'code', $exception ) ) {
				$this->assertEquals( $exception['code'], $e->getCodeString() );
			}
			if ( array_key_exists( 'message', $exception ) ) {
				$this->assertContains( $exception['message'], $e->getMessage() );
			}
		}
	}

	/**
	 * @since 0.5
	 *
	 * @param array $params
	 *
	 * @return ApiBase
	 */
	protected function getModule( $params ) {
		global $wgRequest;

		$requestContext = new RequestContext();
		$request = new FauxRequest( $params, true, $wgRequest->getSessionArray() );
		$requestContext->setRequest( $request );

		$apiMain = new ApiMain( $requestContext );

		$class = $this->getModuleClass();
		return new $class( $apiMain, 'iAmAName' );
	}

	/**
	 * @since 0.5
	 *
	 * @return string Class name for the module being tested
	 */
	abstract protected function getModuleClass();

	/**
	 * @since 0.5
	 *
	 * @return EntityRevisionLookup
	 */
	protected function getMockEntityRevisionLookup() {
		$mock = $this->getMockBuilder( '\Wikibase\EntityRevisionLookup' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getEntityRevision' )
			->will( $this->returnCallback( function( EntityId $entityId, $revisionId = 0 ) {
				return EntityTestHelper::getEntityRevision( $entityId, $revisionId );
			} ) );
		return $mock;
	}

	/**
	 * @since 0.5
	 *
	 * @return EntityTitleLookup
	 */
	protected function getMockEntityTitleLookup() {
		$mock = $this->getMockBuilder( '\Wikibase\EntityTitleLookup' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnValue( $this->getMockTitle() ) );
		return $mock;
	}

	/**
	 * @since 0.5
	 *
	 * @return Title
	 */
	protected function getMockTitle() {
		$mock = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 1 ) );
		$mock->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( 0 ) );
		$mock->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( 'mockPrefixedTitle' ) );
		return $mock;
	}

	/**
	 * @since 0.5
	 *
	 * @return SiteLinkCache
	 */
	protected function getMockSiteLinkCache() {
		$mock = $this->getMockBuilder( '\Wikibase\SiteLinkCache' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->will( $this->returnCallback(
				function( $globalSiteId, $pageTitle ) {
					switch ( $pageTitle ) {
						case 'Berlin':
							return ItemId::newFromNumber( 3 );
						case 'London':
							return ItemId::newFromNumber( 4 );
						case 'Oslo':
							return ItemId::newFromNumber( 5 );
						case 'Episkopi Cantonment':
							return ItemId::newFromNumber( 6 );
					}
					return null;
				}
			) );
		return $mock;
	}

}