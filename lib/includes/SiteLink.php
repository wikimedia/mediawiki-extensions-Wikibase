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
	 * @since 0.1
	 * @var String
	 */
	protected $page;

	/**
	 * @since 0.1
	 * @var Site
	 */
	protected $site;

	/**
	 * @param Site $site The site's global ID, to be used with Sites::singleton()->getSiteByGlobalId().
	 * @param String $page The target page's title. This is expected to already be normalized.
	 */
	public function __construct( Site $site, $page ) {
		$this->page = $page;
		$this->site = $site;
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
		return $this->site->getGlobalId();
	}

	/**
	 * Returns the target site's Site object
	 *
	 * @since 0.1
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
	 * @since 0.1
	 *
	 * @return String
	 */
	public function getUrl() {
		return $this->site->getPagePath( $this->getDBKey() );
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