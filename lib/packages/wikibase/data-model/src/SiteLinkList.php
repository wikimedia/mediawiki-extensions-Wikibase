<?php

namespace Wikibase\DataModel;

use ArrayIterator;
use Comparable;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use OutOfBoundsException;
use Traversable;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdSet;

/**
 * Unordered collection of SiteLink objects.
 * SiteLink objects can be accessed by site id.
 * Only one SiteLink per site id can exist in the collection.
 *
 * @since 0.7
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkList implements IteratorAggregate, Countable, Comparable {

	private $siteLinks = array();

	/**
	 * @param SiteLink[] $siteLinks
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $siteLinks = array() ) {
		foreach ( $siteLinks as $siteLink ) {
			if ( !( $siteLink instanceof SiteLink ) ) {
				throw new InvalidArgumentException( 'SiteLinkList only accepts SiteLink objects' );
			}

			$this->addSiteLink( $siteLink );
		}
	}

	/**
	 * @since 0.8
	 *
	 * @param SiteLink $link
	 *
	 * @throws InvalidArgumentException
	 */
	public function addSiteLink( SiteLink $link ) {
		if ( array_key_exists( $link->getSiteId(), $this->siteLinks ) ) {
			throw new InvalidArgumentException( 'Duplicate site id: ' . $link->getSiteId() );
		}

		$this->siteLinks[$link->getSiteId()] = $link;
	}

	/**
	 * @see SiteLink::__construct
	 *
	 * @since 0.8
	 *
	 * @param string $siteId
	 * @param string $pageName
	 * @param ItemIdSet|ItemId[]|null $badges
	 *
	 * @throws InvalidArgumentException
	 */
	public function addNewSiteLink( $siteId, $pageName, $badges = null ) {
		$this->addSiteLink( new SiteLink( $siteId, $pageName, $badges ) );
	}

	/**
	 * @see IteratorAggregate::getIterator
	 *
	 * Returns a Traversable of SiteLink in which the keys are the site ids.
	 *
	 * @return Traversable|SiteLink[]
	 */
	public function getIterator() {
		return new ArrayIterator( $this->siteLinks );
	}

	/**
	 * @see Countable::count
	 * @return int
	 */
	public function count() {
		return count( $this->siteLinks );
	}

	/**
	 * @param string $siteId
	 *
	 * @return SiteLink
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 */
	public function getBySiteId( $siteId ) {
		if ( !$this->hasLinkWithSiteId( $siteId ) ) {
			throw new OutOfBoundsException( 'No SiteLink with site id: ' . $siteId  );
		}

		return $this->siteLinks[$siteId];
	}

	/**
	 * @since 0.8
	 *
	 * @param string $siteId
	 *
	 * @return boolean
	 * @throws InvalidArgumentException
	 */
	public function hasLinkWithSiteId( $siteId ) {
		if ( !is_string( $siteId ) ) {
			throw new InvalidArgumentException( '$siteId should be a string' );
		}

		return array_key_exists( $siteId, $this->siteLinks );
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

		return $this->siteLinks == $target->siteLinks;
	}

	/**
	 * @since 1.0
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return empty( $this->siteLinks );
	}

	/**
	 * @since 0.8
	 *
	 * @param string $siteId
	 * @throws InvalidArgumentException
	 */
	public function removeLinkWithSiteId( $siteId ) {
		if ( !is_string( $siteId ) ) {
			throw new InvalidArgumentException( '$siteId should be a string' );
		}

		unset( $this->siteLinks[$siteId] );
	}

}
