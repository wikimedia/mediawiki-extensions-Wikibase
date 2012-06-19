<?php

namespace Wikibase;
use MWException;

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
	 * @var reply from the call
	 */
	protected $reply = null;
	protected $content = null;
	protected $status = null;
	protected $url = null;

	/**
	 * Request a page from an external client site
	 * Note that this blocks until the reply arrives or the call times out
	 * @param $siteId string to identify a site in the internal store
	 * @param $titles array used for naming the page on the external site
	 * @return array|null the reply from the external site
	 */
	public function __construct( $siteId, $titles ) {
		global $wgSitename;
		
		// get defaults and adjust them
		$groups = Settings::get( 'siteIdentifiers' );
		$url = $groups['wikipedia']['sites'][$siteId];
		$opts = Settings::get( 'clientPageOpts' );
		$path = Settings::get( 'defaultSiteScript' );
		
		// buil the args for this specific call
		$args = array();
		if ( $titles ) {
			$args['titles'] = join( '|',$titles );
		}
		
		// build a context
		$context = stream_context_create(
			array('http' => array(
				'method' => 'GET',
    			'user_agent'=> wfMsgReplaceArgs(
					Settings::get( 'userAgent' ),
					array( $wgSitename, WB_VERSION )
				),
				'timeout' => Settings::get( 'clientTimeout' ),
				)
			)
		);
		
		// keep the url for later, its often necessary in debugging
		$this->url = $url . wfMsgReplaceArgs( $path, array('api.php?' . wfArrayToCgi( $args, $opts ) ) );
		
		// get the content
		$content = @file_get_contents( $this->url, false, $context );
		$this->content = $content;
		$this->status = $http_response_header[0];
		
		// if everything is okey, then decode and store
		if ( $this->isOk() ) { 
			$this->reply = json_decode( $content, true );
		}
	}
	
	/**
	 * Request a page from an external client site
	 * Note that this blocks until the reply arrives or the call times out
	 * @param $siteId string to identify a site in the internal store
	 * @param $titles.. vararg used for naming the page on the external site
	 * @return array|null the reply from the external site
	 */
	public static function newQuery( $siteId ) {
		$titles = func_get_args();
		array_shift( $titles );
		return new ClientPage( $siteId, $titles );
	}

	/**
	 * Check normalization of a title against the previous query
	 * @param $title string the title to check
	 * @return array the correct normalized entry from the reply
	 */
	public function getNormalized( $title ) {
		$arr = false;
		if ( isset( $this->reply['query']['normalized'] ) ) {
			$arr = array_filter(
				$this->reply['query']['normalized'],
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
	 * @return array the correct converted entry from the reply
	 */
	public function getConverted( $title ) {
		$arr = false;
		if ( isset( $this->reply['query']['converted'] ) ) {
			$arr = array_filter(
				$this->reply['query']['converted'],
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
	 * @return array the correct redirected entry from the reply
	 */
	public function getRedirected( $title ) {
		$arr = false;
		if ( isset( $this->reply['query']['redirects'] ) ) {
			$arr = array_filter(
				$this->reply['query']['redirects'],
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
	 * @return array|null the page structure
	 */
	public function getPage( $title ) {
		$arr = false;
		if ( isset( $this->reply['query']['pages'] ) ) {
			$arr = array_filter(
				array_values( $this->reply['query']['pages'] ),
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
	 * @return array|null the page structure
	 */
	public function lookup( $title ) {
		// the complete rewrite chain
		$title = $this->normalize($title);
		$title = $this->convert($title);
		$title = $this->redirect($title);
		
		// get the correct page
		$page = $this->getPage($title);
		
		// done
		return $page;
	}

	/**
	 * Check wetter there is a reply registered
	 * @return boolean
	 */
	public function hasReply() {
		return is_array( $this->reply );
	}

	/**
	 * Report the complete url used for the request
	 * @return string
	 */
	public function getURL() {
		return $this->url;
	}

	/**
	 * Report the content string from the reply
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Report the status string from the reply
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}
	
	/**
	 * Check if status has the string "200"
	 * @return string
	 */
	public function isOk() {
		return false !== strpos( $this->status, "200" );
	}
}