<?php

namespace Wikibase;

use InvalidArgumentException;

/**
 * Language sorting utility functions.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Mättig
 */
class InterwikiSorter {

	/**
	 * @var array[]
	 */
	private $sortOrders;

	/**
	 * @var string
	 */
	private $sort;

	/**
	 * @var string[]
	 */
	private $sortPrepend;

	/**
	 * @var int[]|null
	 */
	private $sortOrder = null;

	/**
	 * @since 0.4
	 *
	 * @param string $sort
	 * @param array[] $sortOrders
	 * @param string[] $sortPrepend
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $sort, array $sortOrders, array $sortPrepend ) {
		if ( !array_key_exists( 'alphabetic', $sortOrders ) ) {
			throw new InvalidArgumentException(
				'alphabetic interwiki sorting order is missing from Wikibase Client settings.'
			);
		}

		$this->sort = $sort;
		$this->sortOrders = $sortOrders;
		$this->sortPrepend = $sortPrepend;
	}

	/**
	 * Sort an array of links in-place
	 * @version Copied from InterlanguageExtension rev 114818
	 *
	 * @since 0.1
	 *
	 * @param string[] $links
	 *
	 * @return string[]
	 */
	public function sortLinks( array $links ) {
		if ( $this->sortOrder === null ) {
			$this->sortOrder = $this->buildSortOrder( $this->sort, $this->sortOrders );
		}

		// Prepare the array for sorting.
		foreach ( $links as $k => $langLink ) {
			$links[$k] = explode( ':', $langLink, 2 );
		}

		usort( $links, array( $this, 'compareLinks' ) );

		// Restore the sorted array.
		foreach ( $links as $k => $langLink ) {
			$links[$k] = implode( ':', $langLink );
		}

		return $links;
	}

	/**
	 * usort() callback function, compares the links on the basis of $sortOrder
	 *
	 * @param string[] $a
	 * @param string[] $b
	 *
	 * @return int
	 */
	private function compareLinks( $a, $b ) {
		$a = $a[0];
		$b = $b[0];

		if ( $a === $b ) {
			return 0;
		}

		$aIndex = array_key_exists( $a, $this->sortOrder ) ? $this->sortOrder[$a] : null;
		$bIndex = array_key_exists( $b, $this->sortOrder ) ? $this->sortOrder[$b] : null;

		if ( $aIndex === $bIndex ) {
			// If we encounter multiple unknown languages, which may happen if the sort table is not
			// updated, we list them alphabetically.
			return strcmp( $a, $b );
		} elseif ( $aIndex === null ) {
			// Unknown languages must go under the known languages.
			return 1;
		} elseif ( $bIndex === null ) {
			return -1;
		} else {
			return $aIndex - $bIndex;
		}
	}

	/**
	 * Build sort order to be used by compareLinks().
	 *
	 * @param string $sort
	 * @param array[] $sortOrders
	 *
	 * @return int[]
	 */
	private function buildSortOrder( $sort, array $sortOrders ) {
		$sortOrder = $sortOrders['alphabetic'];

		if ( $sort === 'alphabetic' ) {
			// do nothing
		} elseif ( $sort === 'code' ) {
			// The concept of known/unknown languages is irrelevant in strict code order.
			$sortOrder = array();
		} else {
			if ( array_key_exists( $sort, $sortOrders ) ) {
				$sortOrder = $sortOrders[$sort];
			} else {
				// something went wrong but we can use default order
				trigger_error( __CLASS__
					. ' : invalid or unknown sort order specified for interwiki links.', E_USER_WARNING );
				sort( $sortOrder );
			}
		}

		if ( $this->sortPrepend !== array() ) {
			$sortOrder = array_unique( array_merge( $this->sortPrepend, $sortOrder ) );
		}

		return array_flip( $sortOrder );
	}

}
