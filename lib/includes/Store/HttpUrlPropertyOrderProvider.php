<?php

namespace Wikibase\Lib\Store;

use Http;
use Psr\Log\LoggerInterface;

/**
 * PropertyOrderProvider that retrieves the order from a http(s) URL.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class HttpUrlPropertyOrderProvider extends WikiTextPropertyOrderProvider implements PropertyOrderProvider {

	/**
	 * @var Http
	 */
	private $http;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $url;

	/**
	 * @param string $url
	 * @param Http $http
	 * @param LoggerInterface $logger
	 */
	public function __construct( $url, Http $http, LoggerInterface $logger ) {
		$this->url = $url;
		$this->http = $http;
		$this->logger = $logger;
	}

	protected function getPropertyOrderWikitext() {
		$options = [ 'timeout' => 3 ];

		$httpText = $this->http->get( $this->url, $options, __METHOD__ );

		if ( $httpText === false ) {
			$this->logger->debug(
				'{method}: Error loading wikitext from {url}',
				[
					'method' => __METHOD__,
					'url' => $this->url,
				]
			);
			return null;
		}

		return $httpText;
	}

}
