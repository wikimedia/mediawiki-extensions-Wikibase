<?php

namespace Wikibase\DataModel;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Immutable collection of SiteLink objects.
 * SiteLink objects can be accessed by site id.
 * Only one SiteLink per site id can exist in the collection.
 *
 * @since 0.7
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkList implements IteratorAggregate, Countable {

	private $siteLinks = array();

	/**
	 * @param SiteLink[] $siteLinks
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $siteLinks ) {
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

}
