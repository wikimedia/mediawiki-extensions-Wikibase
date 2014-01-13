<?php

namespace Wikibase;

use InvalidArgumentException;
use Site;

/**
 * Class representing a link to another site, based upon the Sites class.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param Site   $site  The site the page link points to
	 * @param String $page  The target page's title. This is expected to already be normalized.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( Site $site, $page ) {
		if ( !is_string( $page ) ) {
			throw new InvalidArgumentException( '$page must be a string' );
		}

		$this->site = $site;
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
	 * Returns the target site's Site object.
	 *
	 * @since 0.1
	 *
	 * @return Site
	 */
	public function getSite() {
		return $this->site;
	}

	/**
	 * Returns the target's full URL.
	 *
	 * @since 0.1
	 *
	 * @return string The URL
	 */
	public function getUrl() {
		return $this->site->getPageUrl( $this->getPage() );
	}

	/**
	 * Returns a string representation of this site link
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function __toString() {
		return '[[' . $this->getSite()->getGlobalId() . ':' . $this->getPage() . ']]';
	}

	/**
	 * Converts a list of SiteLink objects to a structure of arrays.
	 *
	 * @since 0.1
	 *
	 * @param array $baseLinks a list of SiteLink objects
	 * @return array an associative array with on entry for each sitelink
	 */
	public static function siteLinksToArray( $baseLinks ) {
		$links = array();

		/**
		 * @var SiteLink $link
		 */
		foreach ( $baseLinks as $link ) {
			$links[ $link->getSite()->getGlobalId() ] = $link->getPage();
		}

		return $links;
	}

}
