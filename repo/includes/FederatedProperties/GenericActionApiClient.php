<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Http\MwHttpRequestToResponseInterfaceAdapter;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * A Generic MediaWikiAction API client created for use in Federated Properties, but could be used for other cases.
 *
 * This uses MediaWiki's HttpRequestFactory to take advantage of built-in http settings such as timeouts, proxies
 * etc. It uses an adapter to ResponseInterface to allow for consumers to also be compatible with other http clients
 * such as Guzzle.
 * @license GPL-2.0-or-later
 */
class GenericActionApiClient {

	/**
	 * @var HttpRequestFactory
	 */
	private $requestFactory;

	/**
	 * @var string
	 */
	private $repoActionApiUrl;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $userAgent;

	/**
	 * @var string
	 */
	private $userAgentServerName;

	/**
	 * @param HttpRequestFactory $requestFactory
	 * @param string $repoActionApiUrl e.g. https://www.wikidata.org/w/api.php
	 * @param LoggerInterface $logger
	 * @param string $userAgentServerName
	 */
	public function __construct(
		HttpRequestFactory $requestFactory,
		string $repoActionApiUrl,
		LoggerInterface $logger,
		string $userAgentServerName
	) {
		$this->requestFactory = $requestFactory;
		$this->repoActionApiUrl = $repoActionApiUrl;
		$this->logger = $logger;
		$this->userAgentServerName = $userAgentServerName;
	}

	private function getUrlFromParams( array $params ) {
		return wfAppendQuery( $this->repoActionApiUrl, $params );
	}

	/**
	 * @return string
	 */
	public function getUserAgent() {
		if ( $this->userAgent === null ) {
			$this->userAgent = $this->requestFactory->getUserAgent() . ' Wikibase-FederatedProperties ';
			$this->userAgent .= '(' . hash( 'md5', $this->userAgentServerName ) . ')';
		}
		return $this->userAgent;
	}

	public function get( array $params ): ResponseInterface {
		$url = $this->getUrlFromParams( $params );
		$request = $this->requestFactory->create(
			$url,
			[ 'userAgent' => $this->getUserAgent() ],
			__METHOD__
		);

		$request->execute();
		$this->logger->debug( 'Requested: ' . $url );

		if ( $request->getStatus() === 0 ) {
			throw new ApiRequestExecutionException(); // probably hit a timeout
		}

		return new MwHttpRequestToResponseInterfaceAdapter( $request );
	}

}
