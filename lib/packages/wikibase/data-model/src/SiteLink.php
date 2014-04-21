<?php

namespace Wikibase\DataModel;

use Comparable;
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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Michał Łazowik
 */
class SiteLink implements Comparable {

	protected $siteId;
	protected $pageName;

	/**
	 * @var ItemIdSet
	 */
	protected $badges;

	/**
	 * @param string $siteId
	 * @param string $pageName
	 * @param ItemIdSet|ItemId[] $badges
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $siteId, $pageName, $badges = array() ) {
		if ( !is_string( $siteId ) ) {
			throw new InvalidArgumentException( '$siteId needs to be a string' );
		}

		if ( !is_string( $pageName ) ) {
			throw new InvalidArgumentException( '$pageName needs to be a string' );
		}

		$this->siteId = $siteId;
		$this->pageName = $pageName;
		$this->setBadges( $badges );
	}

	private function setBadges( $badges ) {
		if ( is_array( $badges ) ) {
			$badges = new ItemIdSet( $badges );
		}
		elseif ( !( $badges instanceof ItemIdSet ) ) {
			throw new InvalidArgumentException( '$badges needs to be ItemIdSet or ItemId[]' );
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
	 * Returns an array representing the SiteLink, without the siteid.
	 *
	 * @since 0.5
	 * @deprecated
	 *
	 * @return array
	 */
	 public function toArray() {
	 	$array = array(
	 		'name' => $this->pageName,
			'badges' => array()
		);

		foreach ( $this->badges as $badge ) {
			$array['badges'][] = $badge->getSerialization();
		}

		return $array;
	 }

	/**
	 * @since 0.5
	 * @deprecated
	 *
	 * @param string $siteId
	 * @param string|array $data
	 *
	 * @throws InvalidArgumentException
	 * @return SiteLink
	 */
	public static function newFromArray( $siteId, $data ) {
		if ( is_string( $data ) ) {
			// legacy serialization format
			$siteLink = new static( $siteId, $data );
		} else {
			if ( !is_array( $data ) ) {
				throw new InvalidArgumentException( '$data needs to be an array or string (legacy)' );
			}

			if ( !array_key_exists( 'name' , $data ) ) {
				throw new InvalidArgumentException( '$data needs to have a "name" key' );
			}

			$badges = self::getBadgesFromArray( $data );
			$pageName = $data['name'];

			$siteLink = new static( $siteId, $pageName, $badges );
		}

		return $siteLink;
	}

	/**
	 * @since 0.5
	 * @deprecated
	 *
	 * @param array $data
	 *
	 * @return ItemId[]
	 *
	 * @throws InvalidArgumentException
	 */
	protected static function getBadgesFromArray( $data ) {
		if ( !array_key_exists( 'badges', $data ) ) {
			return array();
		}

		if ( !is_array( $data['badges'] ) ) {
			throw new InvalidArgumentException( '$data["badges"] needs to be an array' );
		}

		$badges = array();

		foreach ( $data['badges'] as $badge ) {
			$badges[] = new ItemId( $badge );
		}

		return $badges;
	}

	/**
	 * @see Comparable::equals
	 *
	 * @since 0.7.4
	 *
	 * @param mixed $target
	 *
	 * @return boolean
	 */
	public function equals( $target ) {
		if ( !( $target instanceof self ) ) {
			return false;
		}

		return $this->siteId === $target->siteId
			&& $this->pageName === $target->pageName
			&& $this->badges->equals( $target->badges );
	}

}
