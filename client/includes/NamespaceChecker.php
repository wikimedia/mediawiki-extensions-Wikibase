<?php

namespace Wikibase;

/**
 * @todo perhaps a better name for this class
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class NamespaceChecker {

	protected $excluded;

	protected $enabled;

	/**
 	 * @param $excluded[]
	 * @param $enabled[]
	 *
	 * @throws \MWException
	 */
	public function __construct( array $excluded, $enabled ) {
		$this->excluded = $excluded;

		if ( $enabled !== false && ! is_array( $enabled ) ) {
			throw new \MWException( '$enabled parameter is invalid.' );
		}

		$this->enabled = $enabled;
	}

	public function isWikibaseEnabled( $namespace ) {
		if( !is_int( $namespace ) ) {
			wfDebug( 'Invalid namespace in Wikibase namespace checker.' );
			return false;
		} else if ( in_array( $namespace, $this->excluded ) ) {
			return false;
		} else if ( is_array( $this->enabled ) && !in_array( $namespace, $this->enabled ) ) {
			return false;
		}

		return true;
	}

}
