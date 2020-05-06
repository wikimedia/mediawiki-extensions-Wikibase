<?php

namespace Wikibase\Repo\FederatedProperties;

use MediaWiki\Http\HttpRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

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
	private $logger;

	/**
	 * @param HttpRequestFactory $requestFactory
	 * @param string $repoActionApiUrl e.g. https://www.wikidata.org/w/api.php
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		HttpRequestFactory $requestFactory,
		string $repoActionApiUrl,
		LoggerInterface $logger
	) {
		$this->requestFactory = $requestFactory;
		$this->repoActionApiUrl = $repoActionApiUrl;
		$this->logger = $logger;
	}

	private function getUrlFromParams( array $params ) {
		return wfAppendQuery( $this->repoActionApiUrl, $params );
	}

	public function get( array $params ): ResponseInterface {
		$url = $this->getUrlFromParams( $params );
		$request = $this->requestFactory->create( $url );
		$request->execute();
		$this->logger->debug( 'Requested: ' . $url );
		return new MwHttpRequestToResponseInterfaceAdapter( $request );
	}

}
