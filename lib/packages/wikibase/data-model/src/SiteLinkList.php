<?php

namespace Wikibase\DataModel;

use ArrayIterator;
use Comparable;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use OutOfBoundsException;
use Traversable;

/**
 * Immutable unordered collection of SiteLink objects.
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

	private function addSiteLink( SiteLink $link ) {
		if ( array_key_exists( $link->getSiteId(), $this->siteLinks ) ) {
			throw new InvalidArgumentException( 'Duplicate site id: ' . $link->getSiteId() );
		}

		$this->siteLinks[$link->getSiteId()] = $link;
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
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 */
	public function getBySiteId( $siteId ) {
		if ( !is_string( $siteId ) ) {
			throw new InvalidArgumentException( '$siteId should be a string' );
		}

		if ( !array_key_exists( $siteId, $this->siteLinks ) ) {
			throw new OutOfBoundsException( 'No SiteLink with site id: ' . $siteId  );
		}

		return $this->siteLinks[$siteId];
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

}
