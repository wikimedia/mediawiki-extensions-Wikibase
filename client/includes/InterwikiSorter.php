<?php

namespace Wikibase;
use Wikibase\Settings;

/**
 * Language sorting utility functions.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InterwikiSorter {

	protected $sortOrders;

	protected $sort;

	protected $sortPrepend;

	protected $sortOrder;

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
		wfProfileIn( __METHOD__ );

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

		wfProfileOut( __METHOD__ );
		return $links;
	}

	/**
	 * usort() callback function, compares the links on the basis of $sortOrder
	 *
	 * @since 0.1
	 *
	 * @param mixed $a
	 * @param mixed $b
	 *
	 * @return integer
	 */
	protected function compareLinks( $a, $b ) {
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
	 * @since 0.1
	 *
	 * @param string $sort
	 * @param $sortOrders[]
	 * @param $sortPrepend[]
	 *
	 * @return array
	 */
	protected function buildSortOrder( $sort, array $sortOrders, $sortPrepend ) {
		$sortOrder = $sortOrders['alphabetic'];

		if ( $sort === 'alphabetic' ) {
			// do nothing
		} else if ( $sort === 'alphabetic_revised' ) {
			$sortOrder = $sortOrders['alphabetic_revised'];
		} else if ( $sort === 'alphabetic_sr' ) {
			$sortOrder = $sortOrders['alphabetic_sr'];
		} else if ( $sort === 'code' ) {
			// default code sort order
			sort( $sortOrder );
		} else {
			// something went wrong but we can use default order
			trigger_error( __CLASS__
				. ' : invalid sort order specified for interwiki links.', E_USER_WARNING );
           sort( $sortOrder );
		}

		if( !empty( $sortPrepend ) ) {
			$sortOrder = array_unique( array_merge( $sortPrepend, $sortOrder ) );
		}

		return array_flip( $sortOrder );
	}

}
