<?php

namespace Wikibase\Client\Tests\Integration\Api;

use ApiMain;
use ApiTestContext;
use FauxRequest;
use MediaWikiIntegrationTestCase;
use User;
use Wikibase\Client\Api\ApiClientInfo;
use Wikibase\Lib\SettingsArray;

/**
 * @covers \Wikibase\Client\Api\ApiClientInfo
 *
 * @group Database
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseClient
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ApiClientInfoTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var ApiTestContext
	 */
	protected $apiContext;

	protected function setUp(): void {
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
		$apiQuery = $apiMain->getModuleManager()->getModule( 'query' );

		$apiModule = new ApiClientInfo( $apiQuery, 'query', $this->getSettings() );

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
				],
			],
		];

		$siteid = [ 'siteid' => $settings->getSetting( 'siteGlobalID' ) ];

		return [
			[
				[],
				$this->getApiRequestParams( '' ),
			],
			[
				$repo + $siteid,
				$this->getApiRequestParams( null ),
			],
			[
				$repo + $siteid,
				$this->getApiRequestParams( 'url|siteid' ),
			],
			[
				$repo,
				$this->getApiRequestParams( 'url' ),
			],
			[
				$siteid,
				$this->getApiRequestParams( 'siteid' ),
			],
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
			'wbprop' => $wbprop,
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
