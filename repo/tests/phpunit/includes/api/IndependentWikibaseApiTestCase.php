<?php

namespace Wikibase\Test\Api;

use ApiBase;
use ApiMain;
use FauxRequest;
use RequestContext;
use UsageException;
use Wikibase\Repo\WikibaseRepo;

/**
 * This class can be used instead of the Mediawiki Api TestCase.
 * This class allows us to override services within Wikibase API modules
 *
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author Adam Shorland
 */
abstract class IndependentWikibaseApiTestCase extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		static $isSetup = false;

		\ApiTestCase::$users['wbeditor'] = new \TestUser(
			'Apitesteditor',
			'Api Test Editor',
			'api_test_editor@example.com',
			array( 'wbeditor' )
		);

		$this->setMwGlobals( 'wgUser', self::$users['wbeditor']->user );

		if ( !$isSetup ) {
			//TODO remove me once everything that needs this is overridden
			$sitesTable = WikibaseRepo::getDefaultInstance()->getSiteStore();
			$sitesTable->clear();
			$sitesTable->saveSites( \TestSites::getSites() );
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

		$data = $module->getResult()->getResultData( null, array(
			'BC' => array(),
			'Types' => array(),
			'Strip' => 'all',
		) );
		return $data;
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

}
