<?php

namespace Wikibase\DataModel;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdSet;

/**
 * Immutable value object representing a link to a page on another site.
 *
 * A set of badges, represented as ItemId objects, acts as flags
 * describing attributes of the linked to page.
 *
 * @since 0.4
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Michał Łazowik
 * @author Thiemo Kreuz
 */
class SiteLink {

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var string
	 */
	private $pageName;

	/**
	 * @var ItemIdSet
	 */
	private $badges;

	/**
	 * @param string $siteId
	 * @param string $pageName
	 * @param ItemIdSet|ItemId[]|null $badges
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $siteId, $pageName, $badges = null ) {
		if ( !is_string( $siteId ) || $siteId === '' ) {
			throw new InvalidArgumentException( '$siteId must be a non-empty string' );
		}

		if ( !is_string( $pageName ) || $pageName === '' ) {
			throw new InvalidArgumentException( '$pageName must be a non-empty string' );
		}

		$this->siteId = $siteId;
		$this->pageName = $pageName;
		$this->setBadges( $badges );
	}

	/**
	 * @param ItemIdSet|ItemId[]|null $badges
	 *
	 * @throws InvalidArgumentException
	 */
	private function setBadges( $badges ) {
		if ( $badges === null ) {
			$badges = new ItemIdSet();
		} elseif ( is_array( $badges ) ) {
			$badges = new ItemIdSet( $badges );
		} elseif ( !( $badges instanceof ItemIdSet ) ) {
			throw new InvalidArgumentException(
				'$badges must be an instance of ItemIdSet, an array of instances of ItemId, or null'
			);
		}

		$this->badges = $badges;
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
	 * Badges are not order dependent.
	 *
	 * @since 0.5
	 *
	 * @return ItemId[]
	 */
	public function getBadges() {
		return array_values( iterator_to_array( $this->badges ) );
	}

	/**
	 *
	 * @since 0.7.4
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		return $target instanceof self
			&& $this->siteId === $target->siteId
			&& $this->pageName === $target->pageName
			&& $this->badges->equals( $target->badges );
	}

}
