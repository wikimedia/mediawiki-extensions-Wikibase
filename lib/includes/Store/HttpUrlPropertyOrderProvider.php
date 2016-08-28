<?php

namespace Wikibase\Lib\Store;

use Http;

/**
 * PropertyOrderProvider that retrieves the order from a http(s) URL.
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class HttpUrlPropertyOrderProvider extends WikiTextPropertyOrderProvider implements PropertyOrderProvider {

	/**
	 * @var Http
	 */
	private $http;

	/**
	 * @var string
	 */
	private $url;

	/**
	 * @param string $url
	 * @param Http $http
	 */
	public function __construct( $url, Http $http ) {
		$this->url = $url;
		$this->http = $http;
	}

	protected function getPropertyOrderWikitext() {
		$options = [ 'timeout' => 3 ];

		$httpText = $this->http->get( $this->url, $options, __METHOD__ );

		if ( $httpText === false ) {
			wfDebugLog( 'HttpUrlPropertyOrderProvider', "Error loading wikitext from $this->url\n" );
			return null;
		}

		return $httpText;
	}

}
