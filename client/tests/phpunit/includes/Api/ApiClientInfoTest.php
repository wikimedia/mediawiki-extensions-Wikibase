<?php

namespace Wikibase\Client\Tests\Api;

use ApiMain;
use ApiQuery;
use ApiTestContext;
use FauxRequest;
use User;
use Wikibase\Client\Api\ApiClientInfo;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Client\Api\ApiClientInfo
 *
 * @group Database
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseClient
 *
 * @group medium
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ApiClientInfoTest extends \MediaWikiTestCase {

	/**
	 * @var ApiTestContext
	 */
	protected $apiContext;

	protected function setUp() {
		parent::setUp();

		$this->apiContext = new ApiTestContext();
	}

	/**
	 * @param array $params
	 *
	 * @return ApiClientInfo
	 */
	public function getApiModule( array $params ) {
		$request = new FauxRequest( $params, true );

		$user = User::newFromName( 'zombie' );

		$context = $this->apiContext->newTestContext( $request, $user );
		$apiMain = new ApiMain( $context, true );
		$apiQuery = new ApiQuery( $apiMain, 'wikibase' );

		$apiModule = new ApiClientInfo( $this->getSettings(), $apiQuery, 'query' );

		return $apiModule;
	}

	/**
	 * @dataProvider executeProvider
	 */
	public function testExecute( array $expected, array $params ) {
		$module = $this->getApiModule( $params );
		$module->execute();

		$result = $module->getResult()->getResultData();

		$this->assertEquals( $expected, $result['query']['wikibase'] );
	}

	public function executeProvider() {
		$settings = $this->getSettings();

		$repo = [ 'repo' => [
				'url' => [
					'base' => $settings->getSetting( 'repoUrl' ),
					'scriptpath' => $settings->getSetting( 'repoScriptPath' ),
					'articlepath' => $settings->getSetting( 'repoArticlePath' ),
				]
			]
		];

		$siteid = [ 'siteid' => $settings->getSetting( 'siteGlobalID' ) ];

		return [
			[
				[],
				$this->getApiRequestParams( '' )
			],
			[
				$repo + $siteid,
				$this->getApiRequestParams( null )
			],
			[
				$repo + $siteid,
				$this->getApiRequestParams( 'url|siteid' )
			],
			[
				$repo,
				$this->getApiRequestParams( 'url' )
			],
			[
				$siteid,
				$this->getApiRequestParams( 'siteid' )
			]
		];
	}

	/**
	 * @param string $wbprop
	 *
	 * @return array
	 */
	private function getApiRequestParams( $wbprop ) {
		$params = [
			'action' => 'query',
			'meta' => 'wikibase',
			'wbprop' => $wbprop
		];

		return $params;
	}

	/**
	 * @return SettingsArray
	 */
	private function getSettings() {
		return new SettingsArray( [
			'repoUrl' => 'http://www.example.org',
			'repoScriptPath' => '/w',
			'repoArticlePath' => '/wiki/$1',
			'siteGlobalID' => 'somerandomwiki',
		] );
	}

}
