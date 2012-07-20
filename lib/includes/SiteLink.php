<?php

namespace Wikibase;

/**
 * Class representing a link to another site, based upon the Sites class.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SiteLink {

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
	 * @since 0.1
	 *
	 * @param string $siteID    the external site's id .
	 * @param string $pageTitle the page title to be normalized.
	 *
	 * @throws \MWException
	 * @return String the normalized form of the given page title.
	 */
	public static function normalizePageTitle( $siteID, $pageTitle ) {
		// Check if we have strings as arguments.
		if ( !is_string( $siteID ) ) {
			throw new \MWException( "\$siteID must be a string" );
		}

		if ( !is_string( $pageTitle ) ) {
			throw new \MWException( "\$pageTitle must be a string" );
		}

		$site = Sites::singleton()->getSiteByGlobalId( $siteID );

		if ( !$site ) {
			//XXX: use local normalization?
			return false;
		}

		// Build the args for the specific call
		$args = Settings::get( 'clientPageArgs' );
		$args['titles'] = $pageTitle;

		// Go on call the external site
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			// If the code is under test, don't call out to other sites. Normalize locally.
			// Note: this may cause results to be inconsistent with the actual normalization used by the respective remote site!

			$t = \Title::newFromText( $pageTitle );
			$ret = "{ \"query\" : { \"pages\" : { \"1\" : { \"title\" : " . \FormatJson::encode( $t->getPrefixedText() ) . " } } } }";
		} else {
			$url = $site->getFilePath( 'api.php' ) . '?' . wfArrayToCgi( $args );

			// Go on call the external site
			$ret = \Http::get( $url, Settings::get( 'clientTimeout' ), Settings::get( 'clientPageOpts' ) );
		}

		if ( $ret === false ) {
			//TODO: log. retry?
			return false;
		}

		if ( preg_match( '/^Waiting for [^ ]*: [0-9.-]+ seconds lagged$/', $ret ) ) {
			//TODO: log. retry?
			return false;
		}

		$data = \FormatJson::decode( $ret, true );

		if ( !is_array( $data ) ) {
			return false; //TODO: log this?!
		}

		$page = static::extractPageRecord( $data, $pageTitle );

		if ( !isset( $page['missing'] ) ) {
			//XXX: do what? fail?
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
	 * @since 0.1
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
	 * Creates a new SiteLink representing a link to the given page on the given site. The page title is normalized
	 * for the SiteLink object is created. If you already have a normalized page title, use the constructor directly.
	 *
	 * @note  : This may cause an API request to the remote site, so beware that this function may be slow slow and
	 *        depend on an external service.
	 *
	 * @param String $siteID  The site's global ID, to be used with Sites::singleton()->getSiteByGlobalId().
	 * @param String $page    The target page's title. This is expected to already be normalized.
	 *
	 * @return \Wikibase\SiteLink the new SiteLink
	 * @throws \MWException if the $siteID isn't known.
	 */
	public static function newFromText( $siteID, $page ) {
		$title = static::normalizePageTitle( $siteID, $page );

		if ( $title === false ) {
			throw new \MWException( "failed to normalize title: $page" );
		}

		return new SiteLink( $siteID, $title );
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * @since 0.1
	 * @var String
	 */
	protected $page;

	/**
	 * @since 0.1
	 * @var Site
	 */
	protected $siteID;

	/**
	 * @param String $siteID  The global ID of the site the page link points to
	 * @param String $page    The target page's title. This is expected to already be normalized.
	 */
	public function __construct( $siteID, $page ) {
		if ( !is_string( $siteID ) ) {
			throw new \MWException( '$siteID must be a string' );
		}

		if ( !is_string( $page ) ) {
			throw new \MWException( '$page must be a string' );
		}

		$this->siteID = $siteID;
		$this->page = $page;
	}

	/**
	 * Returns the target page's title, as provided to the constructor.
	 *
	 * @since 0.1
	 *
	 * @return String
	 */
	public function getPage() {
		return $this->page;
	}

	/**
	 * Returns the database form of the target page's title, to be used in MediaWiki URLs.
	 *
	 * @since 0.1
	 *
	 * @return String
	 */
	public function getDBKey() {
		return self::toDBKey( $this->page );
	}

	/**
	 * Returns the target site's global ID.
	 *
	 * @since 0.1
	 *
	 * @return String
	 */
	public function getSiteID() {
		return $this->siteID;
	}

	/**
	 * Returns the target site's Site object
	 *
	 * @since 0.1
	 *
	 * @return Site
	 */
	public function getSite() {
		return Sites::singleton()->getSiteByGlobalId( $this->siteID );
	}

	/**
	 * Returns the target pages's full URL.
	 * Note that depending on the SiteTable, the resulting URL may be protocol relative (i.e. start with //).
	 *
	 * @since 0.1
	 *
	 * @return String|bool The URL of the page, or false if the target site is not known to the Sites class.
	 */
	public function getUrl() {
		$site = $this->getSite();

		if ( !$site ) {
			return false;
		}

		return $site->getPagePath( $this->getDBKey() );
	}

	/**
	 * Returns a string representation of this site link
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getSiteID() . ':' . $this->getDBKey();
	}

	/**
	 * Returns the database form of the given title.
	 *
	 * @since 0.1
	 *
	 * @param String $title the target page's title, in normalized form.
	 *
	 * @return String
	 */
	public static function toDBKey( $title ) {
		return str_replace( ' ', '_', $title );
	}
}