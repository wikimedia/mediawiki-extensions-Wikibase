<?php
namespace Wikibase;

/**
 * Class representing a link to another site, based upon the Sites class.
 */
class SiteLink {

	/**
	 * @var String $title
	 */
	protected $page;

	/**
	 * @var Site $site
	 */
	protected $site;

	/**
	 * @param String $siteID  The site's global ID, to be used with Sites::singleton()->getSiteByGlobalId().
	 * @param String $page    The target page's title. This is expected to already be normalized.
	 *
	 * @throws \MWException if the $siteID isn't known.
	 */
	public function __construct( $siteID, $page ) {
		$this->page = $page;
		$this->site = Sites::singleton()->getSiteByGlobalId( $siteID );

		if ( !$this->site ) {
			throw new \MWException( "unknown site: $siteID" );
		}
	}

	/**
	 * Returns the target page's title, as provided to the constructor.
	 *
	 * @return String
	 */
	public function getPage() {
		return $this->page;
	}

	/**
	 * Returns the database form of the target page's title, to be used in MediaWiki URLs.
	 *
	 * @return String
	 */
	public function getDBKey() {
		return self::toDBKey( $this->page );
	}

	/**
	 * Returns the target site's global ID.
	 *
	 * @return String
	 */
	public function getSiteID() {
		return $this->site->getGlobalId();
	}

	/**
	 * Returns the target site's language code.
	 *
	 * @return String
	 */
	public function getSiteLanguage() {
		return $this->site->getLanguage();
	}

	/**
	 * Returns the target site's Site object
	 *
	 * @return Site
	 */
	public function getSite() {
		return $this->site;
	}

	/**
	 * Returns the target pages's full URL.
	 * Note that depending on the SiteTable, the resulting URL may be protocol relative (i.e. start with //).
	 *
	 * @return String
	 */
	public function getUrl() {
		return $this->site->getPagePath( $this->getDBKey() );
	}

	/**
	 * Returns a string representation of this site link
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getSiteID() . ':' . $this->getDBKey();
	}

	/**
	 * Returns the database form of the given title.
	 *
	 * @param String $title the target page's title, in normalized form.
	 *
	 * @return String
	 */
	public static function toDBKey( $title ) {
		return str_replace( ' ', '_', $title );
	}
}