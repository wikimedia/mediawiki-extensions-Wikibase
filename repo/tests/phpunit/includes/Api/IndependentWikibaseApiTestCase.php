<?php

namespace Wikibase\Repo\Tests\Api;

use ApiBase;
use ApiMain;
use FauxRequest;
use MediaWiki\MediaWikiServices;
use RequestContext;
use ApiUsageException;
use Wikimedia\TestingAccessWrapper;

/**
 * This class can be used instead of the Mediawiki Api TestCase.
 * This class allows us to override services within Wikibase API modules
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
abstract class IndependentWikibaseApiTestCase extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		static $isSetup = false;

		\ApiTestCase::$users['wbeditor'] = new \TestUser(
			'Apitesteditor',
			'Api Test Editor',
			'api_test_editor@example.com',
			[ 'wbeditor' ]
		);

		$this->setMwGlobals( 'wgUser', self::$users['wbeditor']->getUser() );

		if ( !$isSetup ) {
			// TODO: Remove me once everything that needs this is overridden.
			$sitesTable = MediaWikiServices::getInstance()->getSiteStore();
			$sitesTable->clear();
			$sitesTable->saveSites( \TestSites::getSites() );
			$isSetup = true;
		}
	}

	/**
	 * @param array $params
	 *
	 * @return array api request result
	 */
	public function doApiRequest( array $params ) {
		$module = $this->getModule( $params );
		$module->execute();

		$data = $module->getResult()->getResultData( null, [
			'BC' => [],
			'Types' => [],
			'Strip' => 'all',
		] );
		return $data;
	}

	/**
	 * Do the test for exceptions from Api queries.
	 * @param array $params array of params for the api query
	 * @param array $exception Details of the exception to expect (type, code, message, message-key).
	 */
	public function doTestQueryExceptions( $params, $exception ) {
		try {
			$this->doApiRequest( $params );

			$this->fail( 'Failed to throw ApiUsageException' );
		} catch ( ApiUsageException $e ) {
			if ( array_key_exists( 'type', $exception ) ) {
				$this->assertInstanceOf( $exception['type'], $e );
			}

			if ( array_key_exists( 'code', $exception ) ) {
				$msg = TestingAccessWrapper::newFromObject( $e )->getApiMessage();
				$this->assertEquals( $exception['code'], $msg->getApiCode() );
			}

			if ( array_key_exists( 'message', $exception ) ) {
				$this->assertContains( $exception['message'], $e->getMessage() );
			}

			if ( array_key_exists( 'message-key', $exception ) ) {
				$status = $e->getStatusValue();
				$this->assertTrue(
					$status->hasMessage( $exception['message-key'] ),
					'Status message key'
				);
			}
		}
	}

	/**
	 * @param array $params
	 *
	 * @return ApiBase
	 */
	protected function getModule( array $params ) {
		global $wgRequest;

		$requestContext = new RequestContext();
		$request = new FauxRequest( $params, true, $wgRequest->getSessionArray() );
		$requestContext->setRequest( $request );

		$apiMain = new ApiMain( $requestContext, true );

		$class = $this->getModuleClass();
		return new $class( $apiMain, 'iAmAName' );
	}

	/**
	 * @return string Class name for the module being tested
	 */
	abstract protected function getModuleClass();

}
