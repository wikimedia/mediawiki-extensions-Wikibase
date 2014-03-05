<?php

namespace Wikibase\DataModel;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Immutable collection of SiteLink objects.
 *
 * @since 0.7
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkList implements IteratorAggregate, Countable {

	private $siteLinks;

	/**
	 * @param SiteLink[] $siteLinks
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $siteLinks ) {
		$this->assertAreSiteLinks( $siteLinks );
		$this->siteLinks = $siteLinks;
	}

	private function assertAreSiteLinks( array $siteLinks ) {
		foreach ( $siteLinks as $siteLink ) {
			if ( !( $siteLink instanceof SiteLink ) ) {
				throw new InvalidArgumentException( 'SiteLinkList only accepts SiteLink objects' );
			}
		}
	}

	/**
	 * @see IteratorAggregate::getIterator
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
