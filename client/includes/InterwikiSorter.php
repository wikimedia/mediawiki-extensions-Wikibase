<?php

namespace Wikibase;

/**
 * Language sorting utility functions.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InterwikiSorter {

	private $sortOrders;

	private $sort;

	private $sortPrepend;

	private $sortOrder;

	/**
	 * @since 0.4
	 *
	 * @param string $sort
	 * @param $sortOrders[]
	 * @param $sortPrepend[]
	 */
	public function __construct( $sort, array $sortOrders, array $sortPrepend ) {
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
	 * @param $links[]
	 *
	 * @return array
	 */
	public function sortLinks( array $links ) {
		// Prepare the sorting array.
		$this->sortOrder = $this->buildSortOrder(
			$this->sort,
			$this->sortOrders,
			$this->sortPrepend
		);

		// Prepare the array for sorting.
		foreach( $links as $k => $langLink ) {
			$links[$k] = explode( ':', $langLink, 2 );
		}

		usort( $links, array( $this, 'compareLinks' ) );

		// Restore the sorted array.
		foreach( $links as $k => $langLink ) {
			$links[$k] = implode( ':', $langLink );
		}

		return $links;
	}

	/**
	 * usort() callback function, compares the links on the basis of $sortOrder
	 *
	 * @param mixed $a
	 * @param mixed $b
	 *
	 * @return integer
	 */
	private function compareLinks( $a, $b ) {
		$a = $a[0];
		$b = $b[0];

		if( $a == $b ) return 0;

		// If we encounter an unknown language, which may happen if the sort table is not updated, we move it to the bottom.
		$a = array_key_exists( $a, $this->sortOrder ) ? $this->sortOrder[$a] : 999999;
		$b = array_key_exists( $b, $this->sortOrder ) ? $this->sortOrder[$b] : 999999;

		return ( $a > $b ) ? 1 : ( ( $a < $b ) ? -1: 0 );
	}

	/**
	 * Build sort order to be used by compareLinks().
	 *
	 * @param string $sort
	 * @param array $sortOrders []
	 * @param array $sortPrepend []
	 *
	 * @throws \MWException
	 * @return array
	 */
	private function buildSortOrder( $sort, array $sortOrders, array $sortPrepend ) {
		if ( !array_key_exists( 'alphabetic', $sortOrders ) ) {
			throw new \MWException( 'alphabetic interwiki sorting order is missing from Wikibase Client settings.' );
		}

		$sortOrder = $sortOrders['alphabetic'];

		if ( $sort === 'alphabetic' ) {
			// do nothing
		} else if ( $sort === 'code' ) {
			sort( $sortOrder );
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

		if ( $sortPrepend !== array() ) {
			$sortOrder = array_unique( array_merge( $sortPrepend, $sortOrder ) );
		}

		return array_flip( $sortOrder );
	}

}
