<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use ApiQuery;
use FauxRequest;
use MediaWikiIntegrationTestCase;
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
class MetaDataBridgeConfigTest extends MediaWikiIntegrationTestCase {

	private $titleCallbackCalls = [];

	protected function setUp(): void {
		parent::setUp();

		$this->setMwGlobals( 'wgLanguageCode', 'qqx' );
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->titleCallbackCalls = [];
	}

	private function getCustomConfigApiResults( array $fields = [] ): array {
		return $this->getApiResultsWithSettings( new SettingsArray( array_replace( [
				'string-limits' => [
					'VT:string' => [
						'length' => 12345,
					],
				],
				'dataRightsText' => 'Creative Commons CC0 License',
				'dataRightsUrl' => 'https://creativecommons.org/publicdomain/zero/1.0/',
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
			'wbdatabridgeconfig',
			function ( $pagename ) {
				$this->titleCallbackCalls[] = $pagename;
				return 'https://example.com';
			}
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

	public function testExecute_LicenseInfo_defaultConfig() {
		$results = $this->getDefaultConfigApiResults();

		$this->assertArrayHasKey( 'dataRightsUrl', $results );
		$this->assertArrayHasKey( 'dataRightsText', $results );
		$this->assertArrayHasKey( 'termsOfUseUrl', $results );
		$this->assertSame( '(copyrightpage)', $this->titleCallbackCalls[0] );
		$this->assertStringContainsString( 'https://example.com', $results['termsOfUseUrl'] );
	}

	public function testExecute_LicenseInfo_customConfig() {
		$licenseText = 'Example license';
		$licenseUrl = 'https://example.com';
		$results = $this->getCustomConfigApiResults( [
			'dataRightsText' => $licenseText,
			'dataRightsUrl' => $licenseUrl,
		] );

		$this->assertArrayHasKey( 'dataRightsUrl', $results );
		$this->assertSame( $licenseUrl, $results['dataRightsUrl'] );
		$this->assertArrayHasKey( 'dataRightsText', $results );
		$this->assertSame( $licenseText, $results['dataRightsText'] );
	}

	private function getQuery(): ApiQuery {
		$context = new RequestContext();
		$context->setLanguage( 'qqx' );
		$context->setRequest( new FauxRequest() );
		$main = new ApiMain( $context );
		$query = $main->getModuleManager()->getModule( 'query' );

		return $query;
	}

}
