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
	 * @param Http|null $http
	 */
	public function __construct( $url, Http $http = null ) {
		$this->url = $url;
		if ( !$http ) {
			$http = new Http();
		}

		$this->http = $http;
	}

	protected function getOrderedPropertiesPageContent() {
		$httpText = $this->http->get( $this->url, [], __METHOD__ );

		if ( $httpText === false ) {
			wfDebugLog( 'HttpUrlPropertyOrderProvider', "Error loading wikitext from $this->url\n" );
			return null;
		}

		return $httpText;
	}

}
