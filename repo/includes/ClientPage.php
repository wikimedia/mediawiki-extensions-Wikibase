<?php

namespace Wikibase;
use MWException, Http;

/**
 * File for the agent that gets page information from the client.
 *
 * More info can be found at https://www.mediawiki.org/wiki/Extension:Wikibase#clientpage
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ClientPage {

	/**
	 * @var data from the call
	 */
	protected $data = null;

	/**
	 * @var content from the call
	 */
	protected $content = null;

	/**
	 * @var url from the call
	 */
	protected $url = null;

	/**
	 * Request a page from an external client site
	 * Note that this blocks until the reply arrives or the call times out
	 * @param $siteId string to identify a site in the internal store
	 * @param $titles array used for naming the page on the external site
	 */
	public function __construct( $siteId, $titles ) {
		// get defaults and adjust them
		$groups = Settings::get( 'siteIdentifiers' );
		$url = $groups['wikipedia']['sites'][$siteId];
		$path = Settings::get( 'defaultSiteScript' );

		// buil the args for this specific call
		$args = Settings::get( 'clientPageArgs' );
		if ( $titles ) {
			$args['titles'] = join( '|', $titles );
		}

		// keep the url for later, its often necessary in debugging
		$this->url = $url . wfMsgReplaceArgs( $path, array( 'api.php?' . wfArrayToCgi( $args ) ) );

		// It will be nearly impossible to figure out what goes wrong without the status available,
		// the only indication is that there are no json to decode.
		$this->content = Http::get( $this->url, Settings::get( 'clientTimeout' ), Settings::get( 'clientPageOpts' ) );

		// if everything is okey, then decode and store
		if ( is_string( $this->content ) ) {
			$this->data = json_decode( $this->content, true );
		}
	}

	/**
	 * Request a page from an external client site
	 * Note that this blocks until the reply arrives or the call times out
	 * @param $siteId string to identify a site in the internal store
	 * @param $titles,... vararg used for naming the page on the external site
	 * @return ClientPage to hold client data about the referred page
	 */
	public static function newQuery( $siteId ) {
		$titles = func_get_args();
		array_shift( $titles );
		return new ClientPage( $siteId, $titles );
	}

	/**
	 * Check normalization of a title against the previous query
	 * @param $title string the title to check
	 * @return array|false the correct normalized entry from the reply
	 */
	public function getNormalized( $title ) {
		$arr = false;
		if ( isset( $this->data['query']['normalized'] ) ) {
			$arr = array_filter(
				$this->data['query']['normalized'],
				function( $a ) use ( $title ) {
					return $a['from'] === $title;
				}
			);
		}
		return $arr ? $arr[0] : false;
	}

	/**
	 * Check normalization of a title against the previous query
	 * @param $title string the title to check
	 * @return string the normalized form, or the title unchanged
	 */
	public function normalize( $title ) {
		$arr = $this->getNormalized( $title );
		return $arr ? $arr['to'] : $title;
	}

	/**
	 * Check convertion of a title against the previous query
	 * @param $title string the title to check
	 * @return array|false the correct converted entry from the reply
	 */
	public function getConverted( $title ) {
		$arr = false;
		if ( isset( $this->data['query']['converted'] ) ) {
			$arr = array_filter(
				$this->data['query']['converted'],
				function( $a ) use ( $title ) {
					return $a['from'] === $title;
				}
			);
		}
		return $arr ? $arr[0] : false;
	}
	/**
	 * Check conversion of a title against the previous query
	 * @param $title string the title to check
	 * @return string the converted form, or the title unchanged
	 */
	public function convert( $title ) {
		$arr = $this->getConverted( $title );
		return $arr ? $arr['to'] : $title;
	}

	/**
	 * Check redirects of a title against the previous query
	 * @param $title string the title to check
	 * @return array|false the correct redirected entry from the reply
	 */
	public function getRedirected( $title ) {
		$arr = false;
		if ( isset( $this->data['query']['redirects'] ) ) {
			$arr = array_filter(
				$this->data['query']['redirects'],
				function( $a ) use ( $title ) {
					return $a['from'] === $title;
				}
			);
		}
		return $arr ? $arr[0] : false;
	}

	/**
	 * Check redirects of a title against the previous query
	 * @param $title string the title to check
	 * @return string the redirected form, or the title unchanged
	 */
	public function redirect( $title ) {
		$arr = $this->getRedirected( $title );
		return $arr ? $arr['to'] : $title;
	}

	/**
	 * Check pages of a title against the previous query
	 * @param $title string the title to check
	 * @return array|false the page structure
	 */
	public function getPage( $title ) {
		$arr = false;
		if ( isset( $this->data['query']['pages'] ) ) {
			$arr = array_filter(
				array_values( $this->data['query']['pages'] ),
				function( $a ) use ( $title ) {
					return $a['title'] === $title;
				}
			);
		}
		return $arr ? $arr[0] : false;
	}

	/**
	 * Check both conversion and normalization of a page against the previous query
	 * @param $title string the title to check
	 * @return array|false the page structure
	 */
	public function lookup( $title ) {
		// the complete rewrite chain
		$title = $this->normalize( $title );
		$title = $this->convert( $title );
		$title = $this->redirect( $title );

		// get the correct page
		$page = $this->getPage( $title );

		// done
		return $page;
	}

	/**
	 * Report the complete url used for the request
	 * @return string
	 */
	public function getURL() {
		return $this->url;
	}

	/**
	 * Check wetter there is a reply and if that has resultet in any content
	 * @return boolean
	 */
	public function hasContent() {
		return is_string( $this->content );
	}

	/**
	 * Report the content string from the reply
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Check wetter there is a reply and if that has resultet in any data
	 * @return boolean
	 */
	public function hasData() {
		return is_array( $this->data );
	}

	/**
	 * Report the parsed data from the reply
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}
}