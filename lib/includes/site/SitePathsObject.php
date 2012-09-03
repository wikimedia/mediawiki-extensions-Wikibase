<?php

/**
 * Implementation of the SitePaths interface.
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
 * @file
 * @since 1.20
 *
 * @ingroup Site
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SitePathsObject implements SitePaths {

	protected $paths;

	/**
	 * Constructor.
	 *
	 * @since 1.20
	 *
	 * @param array $paths
	 */
	public function __construct( array $paths = array() ) {
		$this->paths = $paths;
	}

	/**
	 * @see SitePaths::setPath
	 *
	 * @since 1.20
	 *
	 * @param string $pathType
	 * @param string $fullUrl
	 */
	public function setPath( $pathType, $fullUrl ) {
		$this->paths[$pathType] = $fullUrl;
	}

	/**
	 * @see SitePaths::getPath
	 *
	 * @since 1.20
	 *
	 * @param string $pathType
	 *
	 * @return string|false
	 */
	public function getPath( $pathType ) {
		return array_key_exists( $pathType, $this->paths ) ? $this->paths[$pathType] : false;
	}

	/**
	 * @see SitePaths::getAll
	 *
	 * @since 1.20
	 *
	 * @return array of string
	 */
	public function getAll() {
		return $this->paths;
	}

	/**
	 * @see SitePaths::removePath
	 *
	 * @since 1.20
	 *
	 * @param string $pathType
	 */
	public function removePath( $pathType ) {
		unset( $this->paths[$pathType] );
	}

}