<?php

namespace Wikibase\DataModel;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use Iterator;
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
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkList implements IteratorAggregate, Countable {

	/**
	 * @var SiteLink[]
	 */
	private $siteLinks = [];

	/**
	 * @param iterable|SiteLink[] $siteLinks Can be a non-array iterable since 8.1
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( /* iterable */ $siteLinks = [] ) {
		if ( !is_array( $siteLinks ) && !( $siteLinks instanceof Traversable ) ) {
			throw new InvalidArgumentException( '$siteLinks must be iterable' );
		}

		foreach ( $siteLinks as $siteLink ) {
			if ( !( $siteLink instanceof SiteLink ) ) {
				throw new InvalidArgumentException( 'Every element of $siteLinks must be an instance of SiteLink' );
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
	 * @since 2.5
	 *
	 * @param SiteLink $link
	 */
	public function setSiteLink( SiteLink $link ) {
		$this->siteLinks[$link->getSiteId()] = $link;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $siteId
	 * @param string $pageName
	 * @param ItemIdSet|ItemId[]|null $badges
	 */
	public function setNewSiteLink( $siteId, $pageName, $badges = null ) {
		$this->setSiteLink( new SiteLink( $siteId, $pageName, $badges ) );
	}

	/**
	 * @see IteratorAggregate::getIterator
	 *
	 * Returns an Iterator of SiteLink in which the keys are the site ids.
	 *
	 * @return Iterator|SiteLink[]
	 */
	public function getIterator(): Traversable {
		return new ArrayIterator( $this->siteLinks );
	}

	/**
	 * @see Countable::count
	 *
	 * @return int
	 */
	public function count(): int {
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
			throw new OutOfBoundsException( 'SiteLink with siteId "' . $siteId . '" not found' );
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
			throw new InvalidArgumentException( '$siteId must be a string; got ' . gettype( $siteId ) );
		}

		return array_key_exists( $siteId, $this->siteLinks );
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
			&& $this->siteLinks == $target->siteLinks;
	}

	/**
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return empty( $this->siteLinks );
	}

	/**
	 * @since 2.5
	 *
	 * @return SiteLink[] Array indexed by site id.
	 */
	public function toArray() {
		return $this->siteLinks;
	}

	/**
	 * @since 0.8
	 *
	 * @param string $siteId
	 *
	 * @throws InvalidArgumentException
	 */
	public function removeLinkWithSiteId( $siteId ) {
		if ( !is_string( $siteId ) ) {
			throw new InvalidArgumentException( '$siteId must be a string; got ' . gettype( $siteId ) );
		}

		unset( $this->siteLinks[$siteId] );
	}

}
