<?php

namespace Wikibase\Repo\FederatedProperties;

use MediaWiki\Http\HttpRequestFactory;
use Psr\Http\Message\ResponseInterface;

/**
 * A Generic MediaWikiAction API client created for use in Federated Properties, but could be used for other cases.
 *
 * This uses MediaWiki's HttpRequestFactory to take advantage of built-in http settings such as timeouts, proxies
 * etc. It uses an adapter to ResponseInterface to allow for consumers to also be compatible with other http clients
 * such as Guzzle.
 */
class GenericActionApiClient {

	private $requestFactory;
	private $repoActionApiUrl;

	/**
	 * @param HttpRequestFactory $requestFactory
	 * @param string $repoActionApiUrl e.g. https://www.wikidata.org/w/api.php
	 */
	public function __construct( HttpRequestFactory $requestFactory, string $repoActionApiUrl ) {
		$this->requestFactory = $requestFactory;
		$this->repoActionApiUrl = $repoActionApiUrl;
	}

	private function getUrlFromParams( array $params ) {
		return wfAppendQuery( $this->repoActionApiUrl, $params );
	}

	public function get( array $params ): ResponseInterface {
		$request = $this->requestFactory->create( $this->getUrlFromParams( $params ) );
		$request->execute();
		return new MwHttpRequestToResponseInterfaceAdapter( $request );
	}

}
