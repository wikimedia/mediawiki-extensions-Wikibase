<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use ApiQuery;
use FauxRequest;
use PHPUnit\Framework\TestCase;
use RequestContext;
use Wikibase\Repo\Api\MetaDataBridgeConfig;
use Wikibase\SettingsArray;

/**
 * @covers \Wikibase\Repo\Api\MetaDataBridgeConfig
 *
 * @group API
 * @group Wikibase
 * @group WikibaseApi
 *
 * @license GPL-2.0-or-later
 */
class MetaDataBridgeConfigTest extends TestCase {

	public function testExecute_customConfig() {
		$repoSettings = new SettingsArray( [
			'string-limits' => [
				'VT:string' => [
					'length' => 12345,
				],
			],
		] );
		$api = new MetaDataBridgeConfig(
			$repoSettings,
			$this->getQuery(),
			'wbdatabridgeconfig'
		);

		$api->execute();
		$apiResult = $api->getResult();
		$results = $apiResult->getResultData()['query']['wbdatabridgeconfig'];

		$this->assertArrayHasKey( 'dataTypeLimits', $results );
		$dataTypeLimits = $results['dataTypeLimits'];
		$this->assertArrayHasKey( 'string', $dataTypeLimits );
		$stringLimits = $dataTypeLimits['string'];
		$this->assertArrayHasKey( 'maxLength', $stringLimits );
		$this->assertSame( 12345, $stringLimits['maxLength'] );
	}

	public function testExecute_defaultConfig() {
		$repoSettings = new SettingsArray(
			require __DIR__ . '/../../../../config/Wikibase.default.php'
		);
		$api = new MetaDataBridgeConfig(
			$repoSettings,
			$this->getQuery(),
			'wbdatabridgeconfig'
		);

		$api->execute();
		$apiResult = $api->getResult();
		$results = $apiResult->getResultData()['query']['wbdatabridgeconfig'];

		$this->assertArrayHasKey( 'dataTypeLimits', $results );
		$dataTypeLimits = $results['dataTypeLimits'];
		$this->assertArrayHasKey( 'string', $dataTypeLimits );
		$stringLimits = $dataTypeLimits['string'];
		$this->assertArrayHasKey( 'maxLength', $stringLimits );
		$this->assertSame( 400, $stringLimits['maxLength'] );
	}

	private function getQuery(): ApiQuery {
		$context = new RequestContext();
		$context->setLanguage( 'qqx' );
		$context->setRequest( new FauxRequest() );
		$main = new ApiMain( $context );
		$query = new ApiQuery( $main, 'query' );

		return $query;
	}

}
