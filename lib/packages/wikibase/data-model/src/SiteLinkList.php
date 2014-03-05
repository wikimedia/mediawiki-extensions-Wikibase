<?php

namespace Wikibase\DataModel;

use InvalidArgumentException;

/**
 * Immutable collection of SiteLink objects.
 *
 * @since 0.7
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkList implements \Iterator {

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

	public function current() {
		// TODO: Implement current() method.
	}

	public function next() {
		// TODO: Implement next() method.
	}

	public function key() {
		// TODO: Implement key() method.
	}

	public function valid() {
		// TODO: Implement valid() method.
	}

	public function rewind() {
		// TODO: Implement rewind() method.
	}

}
