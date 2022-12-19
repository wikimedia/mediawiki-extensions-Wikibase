<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties;

use Exception;
use MockHttpTrait;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Repo\WikibaseRepo;

/**
 * This trait can be used to perform some standard actions on the fed props settings that may be
 * desired during tests.
 *
 * @author Addshore
 * @license GPL-2.0-or-later
 */
trait FederatedPropertiesTestTrait {
	use MockHttpTrait;

	protected function setSourceWikiUnavailable() {
		$this->installMockHttp( $this->makeFakeHttpRequest( '', 0 ) );
		$this->setWbSetting( 'federatedPropertiesSourceScriptUrl', '255.255.255.255/' );
	}

	protected function setFederatedPropertiesEnabled( bool $withLocalPropertySource = false ): void {
		$this->setWbSetting( 'federatedPropertiesEnabled', true );

		$localEntitySource = [
			'entityNamespaces' => [ 'item' => 120, 'property' => 122 ],
			'repoDatabase' => false,
			'baseUri' => 'http://wikidata-federated-properties.wmflabs.org/entity/',
			'interwikiPrefix' => '',
			'rdfNodeNamespacePrefix' => 'wd',
			'rdfPredicateNamespacePrefix' => 'wdt',
		];

		$this->setWbSetting( 'entitySources', [
			'local' => $localEntitySource,
			'fedprops' => [
				'entityTypes' => [ 'property' ],
				'baseUri' => $this->getFederatedPropertiesSourceConceptUri(),
				'interwikiPrefix' => 'wikidatabeta',
				'rdfNodeNamespacePrefix' => 'fpwd',
				'rdfPredicateNamespacePrefix' => 'fpwd',
				'type' => 'api',
			],
		] );
		$this->setWbSetting( 'localEntitySourceName', 'local' );
	}

	protected function newFederatedPropertyIdFromPId( string $pId ): FederatedPropertyId {
		return new FederatedPropertyId( $this->getFederatedPropertiesSourceConceptUri() . $pId, $pId );
	}

	private function getFederatedPropertiesSourceConceptUri(): string {
		return 'http://wikidata.beta.wmflabs.org/entity/';
	}

	private function setWbSetting( string $name, $value ) {
		$this->setWbSettingInGlobalIfMwIntegrationTest( $name, $value );
		$this->setWbSettingInSettings( $name, $value );
	}

	private function setWbSettingInSettings( string $name, $value ) {
		$settings = WikibaseRepo::getSettings();
		$settings->setSetting( $name, $value );
	}

	/**
	 * @param array $requestResponsePairs list of [ $requestParams, $jsonResponse ] pairs. The former are used to match request URL, the
	 *        latter are used as the response body.
	 */
	protected function mockSourceApiRequests( array $requestResponsePairs ): void {
		$this->installMockHttp( function ( $url ) use ( $requestResponsePairs ) {
			return $this->makeFakeHttpRequest(
				json_encode( $this->matchResponseToUrl( $url, $requestResponsePairs ) ),
				200,
				[ 'some' => 'header' ] // MwHttpRequestToResponseInterfaceAdapter needs this to be non-empty.
			);
		} );
	}

	/**
	 * Little homebrew url params matcher. It might make sense to look into a url matching and/or proper request mocking library instead.
	 */
	private function matchResponseToUrl( string $url, array $requestResponsePairs ): array {
		parse_str(
			parse_url( $url, PHP_URL_QUERY ),
			$urlParams
		);

		foreach ( $requestResponsePairs as [ $requestParams, $response ] ) {
			foreach ( $requestParams as $name => $value ) {
				if ( $urlParams[$name] !== $value ) {
					continue 2; // mismatch, continue with the next request/response pair
				}
			}

			return $response; // all params matched!
		}

		throw new Exception( "No mock request defined for url: '$url'" );
	}

	/**
	 * Only set the global value if this trait is being used in a MediaWiki integration test.
	 * These tests will automatically reset the global at the end of processing a test.
	 */
	private function setWbSettingInGlobalIfMwIntegrationTest( string $name, $value ) {
		if ( method_exists( $this, 'setMwGlobals' ) ) {
			global $wgWBRepoSettings;
			$newRepoSettings = $wgWBRepoSettings;
			$newRepoSettings[$name] = $value;
			$this->setMwGlobals( 'wgWBRepoSettings', $newRepoSettings );
		}
	}

	public function testFederatedPropertiesEnabled() {
		$settings = WikibaseRepo::getSettings();
		$this->assertSame( true, $settings->getSetting( 'federatedPropertiesEnabled' ) );
	}

}
