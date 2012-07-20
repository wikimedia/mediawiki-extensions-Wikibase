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
 */
class SiteLink {

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
		$site = Sites::singleton()->getSiteByGlobalId( $siteID );

		if ( $site === false ) {
			throw new \MWException( "unknown site: $siteID" );
		}

		$title = $site->normalizePageName( $page );

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