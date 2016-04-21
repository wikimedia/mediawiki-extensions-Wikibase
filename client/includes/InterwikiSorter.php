<?php

namespace Wikibase;

/**
 * Language sorting utility functions.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Mättig
 */
class InterwikiSorter {

	/**
	 * @see Documentation of "sort" and "interwikiSortOrders" options in docs/options.wiki.
	 */
	const SORT_CODE = 'code';

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
	 */
	public function __construct( $sort, array $sortOrders = [], array $sortPrepend = [] ) {
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
	private function compareLinks( array $a, array $b ) {
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
		if ( $sort === self::SORT_CODE ) {
			// The concept of known/unknown languages is irrelevant in strict code order.
			$sortOrder = [];
		} elseif ( !array_key_exists( $sort, $sortOrders ) ) {
			// Something went wrong, but we can use default "code" order.
			wfDebugLog( __CLASS__, __FUNCTION__
				. ': Invalid or unknown sort order specified for interwiki links.' );
			$sortOrder = [];
		} else {
			$sortOrder = $sortOrders[$sort];
		}

		if ( $this->sortPrepend !== [] ) {
			$sortOrder = array_unique( array_merge( $this->sortPrepend, $sortOrder ) );
		}

		return array_flip( $sortOrder );
	}

}
