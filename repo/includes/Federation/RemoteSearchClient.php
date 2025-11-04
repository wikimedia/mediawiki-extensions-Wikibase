<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Federation;

use MediaWiki\Http\HttpRequestFactory;
use Wikibase\Lib\SettingsArray;

class RemoteSearchClient {

	private HttpRequestFactory $httpRequestFactory;
	private SettingsArray $settings;

	/**
	 * Builds the query parameters for a remote wbsearchentities request.
	 *
	 * Accepts validated local API params and converts them into the
	 * format expected by the remote Wikibase endpoint.
	 *
	 * @param array $params Extracted API request parameters
	 * @return array Query parameters for the remote request
	 */
	private function buildRemoteParams( array $params ): array {
		$remoteParams = [
			'action'      => 'wbsearchentities',
			'format'      => 'json',
			'errorformat' => 'plaintext',
			'search'      => (string)$params['search'],
			'language'    => (string)$params['language'],
			'uselang'     => (string)( $params['uselang'] ?? $params['language'] ),
			'type'        => (string)$params['type'],
		];

		if ( isset( $params['limit'] ) ) {
			$remoteParams['limit'] = (int)$params['limit'];
		}

		if ( isset( $params['continue'] ) ) {
			$remoteParams['continue'] = (int)$params['continue'];
		}

		if ( array_key_exists( 'strictlanguage', $params ) ) {
			$remoteParams['strictlanguage'] = $params['strictlanguage'] ? 1 : 0;
		}

		if ( isset( $params['profile'] ) ) {
			$remoteParams['profile'] = (string)$params['profile'];
		}

		if ( isset( $params['props'] ) ) {
			$remoteParams['props'] = is_array( $params['props'] )
				? implode( '|', $params['props'] )
				: (string)$params['props'];
		}

		return $remoteParams;
	}

	public function __construct(
		HttpRequestFactory $httpRequestFactory,
		SettingsArray $settings
	) {
		$this->httpRequestFactory = $httpRequestFactory;
		$this->settings = $settings;
	}

	/**
	 * Performs the remote wbsearchentities request and returns
	 * the decoded JSON response as an associative array.
	 *
	 * @param array $params
	 * @return array Decoded wbsearchentities response, or [] on failure
	 * @throws ApiUsageException
	 */
	public function searchEntities( array $params ): array {
		$remoteParams = $this->buildRemoteParams( $params );

		// For now hard-coded; later read from federation config in $this->settings
		$remoteUrl = 'https://www.wikidata.org/w/api.php?' . \wfArrayToCgi( $remoteParams );

		$req = $this->httpRequestFactory->create( $remoteUrl, [
			'method'  => 'GET',
			'timeout' => 10,
		] );

		$status = $req->execute();
		if ( !$status->isOK() ) {
			return [];
		}

		$resp = \FormatJson::decode( $req->getContent(), true );
		return is_array( $resp ) ? $resp : [];
	}
}
