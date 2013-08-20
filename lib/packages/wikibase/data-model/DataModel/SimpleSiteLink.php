<?php

namespace Wikibase\DataModel;

use InvalidArgumentException;

/**
 * Class representing a link to another site.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Michał Łazowik
 */
class SimpleSiteLink {

	protected $siteId;
	protected $pageName;
	protected $badges;

	public function __construct( $siteId, $pageName, $badges = array() ) {
		if ( !is_string( $siteId ) ) {
			throw new InvalidArgumentException( '$siteId needs to be a string' );
		}

		if ( !is_string( $pageName ) ) {
			throw new InvalidArgumentException( '$pageName needs to be a string' );
		}

		if ( !is_array( $badges ) ) {
			throw new InvalidArgumentException( '$badges needs to be an array' );
		}

		foreach( $badges as $badge ) {
			if ( !( $badge instanceof Entity\ItemId ) ) {
				throw new InvalidArgumentException( 'Each value of $badges needs to be a ItemId' );
			}
		}

		$this->siteId = $siteId;
		$this->pageName = $pageName;
		$this->badges = array_values( $badges );
	}

	/**
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getSiteId() {
		return $this->siteId;
	}

	/**
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getPageName() {
		return $this->pageName;
	}

	/**
	 * @since 0.5
	 *
	 * Returns an array of ItemIds
	 *
	 * @return array
	 */
	public function getBadges() {
		return $this->badges;
	}

}
