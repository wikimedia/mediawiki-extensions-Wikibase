<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use ApiQuery;
use FauxRequest;
use PHPUnit\Framework\TestCase;
use RequestContext;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Api\MetaDataBridgeConfig;

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

	private function getCustomConfigApiResults( array $fields = [] ): array {
		return $this->getApiResultsWithSettings( new SettingsArray( array_replace( [
				'string-limits' => [
					'VT:string' => [
						'length' => 12345,
					],
				],
			], $fields ) )
		);
	}

	private function getDefaultConfigApiResults(): array {
		return $this->getApiResultsWithSettings(
			new SettingsArray(
				require __DIR__ . '/../../../../config/Wikibase.default.php'
			)
		);
	}

	private function getApiResultsWithSettings( SettingsArray $repoSettings ): array {
		$api = new MetaDataBridgeConfig(
			$repoSettings,
			$this->getQuery(),
			'wbdatabridgeconfig'
		);

		$api->execute();
		$apiResult = $api->getResult();
		return $apiResult->getResultData()['query']['wbdatabridgeconfig'];
	}

	public function testExecute_StringMaxLength_customConfig() {
		$results = $this->getCustomConfigApiResults();

		$this->assertArrayHasKey( 'dataTypeLimits', $results );
		$dataTypeLimits = $results['dataTypeLimits'];
		$this->assertArrayHasKey( 'string', $dataTypeLimits );
		$stringLimits = $dataTypeLimits['string'];
		$this->assertArrayHasKey( 'maxLength', $stringLimits );
		$this->assertSame( 12345, $stringLimits['maxLength'] );
	}

	public function testExecute_StringMaxLength_defaultConfig() {
		$results = $this->getDefaultConfigApiResults();

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
