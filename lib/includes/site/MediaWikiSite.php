<?php

/**
 * Class representing a MediaWiki site.
 *
 * @since 1.20
 *
 * @file
 * @ingroup Site
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MediaWikiSite extends SiteObject {

	/**
	 * @since 0.1
	 *
	 * @param integer $globalId
	 *
	 * @return MediaWikiSite
	 */
	public static function newFromGlobalId( $globalId ) {
		return SitesTable::singleton()->newRow( array(
			'type' => Site::TYPE_MEDIAWIKI,
			'global_key' => $globalId,
		) );
	}

	/**
	 * Returns the normalized form of the given page title, using the normalization rules of the given site.
	 * If the given title is a redirect, the redirect weill be resolved and the redirect target is returned.
	 *
	 * @note  : This actually makes an API request to the remote site, so beware that this function is slow and depends
	 *        on an external service.
	 *
	 * @note  : If MW_PHPUNIT_TEST is set, the call to the external site is skipped, and the title is normalized using
	 *        the local normalization rules as implemented by the Title class.
	 *
	 * @see Site::normalizePageName
	 *
	 * @since 1.20
	 *
	 * @param string $pageName
	 *
	 * @return string
	 */
	public function normalizePageName( $pageName ) {
		// Check if we have strings as arguments.
		if ( !is_string( $pageName ) ) {
			throw new MWException( "\$pageTitle must be a string" );
		}

		// Build the args for the specific call
		$args = \Wikibase\Settings::get( 'clientPageArgs' ); // FIXME
		$args['titles'] = $pageName;

		// Go on call the external site
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			// If the code is under test, don't call out to other sites. Normalize locally.
			// Note: this may cause results to be inconsistent with the actual normalization used by the respective remote site!

			$t = Title::newFromText( $pageName );
			$ret = "{ \"query\" : { \"pages\" : { \"1\" : { \"title\" : " . FormatJson::encode( $t->getPrefixedText() ) . " } } } }";
		} else {
			$url = $this->getFilePath( 'api.php' ) . '?' . wfArrayToCgi( $args );

			// Go on call the external site
			$ret = Http::get( $url, Settings::get( 'clientTimeout' ), Settings::get( 'clientPageOpts' ) );
		}

		if ( $ret === false ) {
			//TODO: log. retry?
			return false;
		}

		if ( preg_match( '/^Waiting for [^ ]*: [0-9.-]+ seconds lagged$/', $ret ) ) {
			//TODO: log. retry?
			return false;
		}

		$data = FormatJson::decode( $ret, true );

		if ( !is_array( $data ) ) {
			//TODO: log.
			return false;
		}

		$page = static::extractPageRecord( $data, $pageName );

		if ( isset( $page['missing'] ) ) {
			//TODO: log.
			return false;
		}

		if ( !isset( $page['title'] ) ) {
			//TODO: log.
			return false;
		}

		return $page['title'];
	}

	/**
	 * Get normalization record for a given page title from an API response.
	 *
	 * @since 1.20
	 *
	 * @param array $externalData A reply from the API on a external server.
	 * @param string $pageTitle Identifies the page at the external site, needing normalization.
	 *
	 * @return array|false a 'page' structure representing the page identified by $pageTitle.
	 */
	private static function extractPageRecord( $externalData, $pageTitle ) {
		// If there is a special case with only one returned page
		// we can cheat, and only return
		// the single page in the "pages" substructure.
		if ( isset( $externalData['query']['pages'] ) ) {
			$pages = array_values( $externalData['query']['pages'] );
			if ( count( $pages) === 1 ) {
				return $pages[0];
			}
		}
		// This is only used during internal testing, as it is assumed
		// a more optimal (and lossfree) storage.
		// Make initial checks and return if prerequisites are not meet.
		if ( !is_array( $externalData ) || !isset( $externalData['query'] ) ) {
			return false;
		}
		// Loop over the tree different named structures, that otherwise are similar
		$structs = array(
			'normalized' => 'from',
			'converted' => 'from',
			'redirects' => 'from',
			'pages' => 'title'
		);
		foreach ( $structs as $listId => $fieldId ) {
			// Check if the substructure exist at all.
			if ( !isset( $externalData['query'][$listId] ) ) {
				continue;
			}
			// Filter the substructure down to what we actually are using.
			$collectedHits = array_filter(
				array_values( $externalData['query'][$listId] ),
				function( $a ) use ( $fieldId, $pageTitle ) {
					return $a[$fieldId] === $pageTitle;
				}
			);
			// If still looping over normalization, conversion or redirects,
			// then we need to keep the new page title for later rounds.
			if ( $fieldId === 'from' && is_array( $collectedHits ) ) {
				switch ( count( $collectedHits ) ) {
					case 0:
						break;
					case 1:
						$pageTitle = $collectedHits[0]['to'];
						break;
					default:
						return false;
				}
			}
			// If on the pages structure we should prepare for returning.
			elseif ( $fieldId === 'title' && is_array( $collectedHits ) ) {
				switch ( count( $collectedHits ) ) {
					case 0:
						return false;
					case 1:
						return array_shift( $collectedHits );
					default:
						return false;
				}
			}
		}
		// should never be here
		return false;
	}

	/**
	 * Returns the relative page path.
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getRelativePagePath() {
		return $this->getExtraData( 'page_path', '' );
	}

	/**
	 * Returns the relative file path.
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getRelativeFilePath() {
		return $this->getExtraData( 'file_path', '' );
	}

	/**
	 * Returns the base URL, ie http://www.wikidata.org
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getUrl() {
		return $this->getExtraData( 'url', '' );
	}

	/**
	 * Sets the base URL.
	 *
	 * @since 1.20
	 *
	 * @param string $url
	 */
	public function setUrl( $url ) {
		$this->setExtraData( 'url', $url );

		if ( $url !== '' ) {
			$url = strrev( $url ) . '.';
		}

		$this->setField( 'domain', $url );
	}

	/**
	 * Sets the relative page path.
	 *
	 * @since 1.20
	 *
	 * @param string $path
	 */
	public function setRelativePagePath( $path ) {
		$this->setExtraData( 'page_path', $path );
	}

	/**
	 * Sets the relative file path.
	 *
	 * @since 1.20
	 *
	 * @param string $path
	 */
	public function setRelativeFilePath( $path ) {
		$this->setExtraData( 'file_path', $path );
	}

	/**
	 * @see Site::getPagePath
	 *
	 * @since 1.20
	 *
	 * @param string|false $pageName
	 *
	 * @return string
	 */
	public function getPagePath( $pageName = false ) {
		$pagePath = $this->getUrl() . $this->getRelativePagePath();

		if ( $pageName !== false ) {
			$pagePath = str_replace( '$1', rawurlencode( $pageName ), $pagePath );
		}

		return $pagePath;
	}

	/**
	 * Returns the full file path (ie site url + relative file path).
	 * The path should go at the $1 marker. If the $path
	 * argument is provided, the marker will be replaced by it's value.
	 *
	 * @since 1.20
	 *
	 * @param string|false $path
	 *
	 * @return string
	 */
	public function getFilePath( $path = false ) {
		$filePath = $this->getUrl() . $this->getRelativeFilePath();

		if ( $filePath !== false ) {
			$filePath = str_replace( '$1', $path, $filePath );
		}

		return $filePath;
	}

}
