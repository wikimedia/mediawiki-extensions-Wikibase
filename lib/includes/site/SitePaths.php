<?php

/**
 * Interface for collections of paths for a single site.
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
interface SitePaths {

	/**
	 * Sets the provided url as path of the specified type.
	 *
	 * @since 1.20
	 *
	 * @param string $pathType
	 * @param string $fullUrl
	 */
	public function setPath( $pathType, $fullUrl );

	/**
	 * Returns the path of the provided type or false is there is no such path.
	 *
	 * @since 1.20
	 *
	 * @param string $pathType
	 *
	 * @return string|false
	 */
	public function getPath( $pathType );

	/**
	 * Returns the paths as associative array.
	 * The keys are path types, the values are the path urls.
	 *
	 * @since 1.20
	 *
	 * @return array of string
	 */
	public function getAll();

	/**
	 * Removes the path of the provided type if it's set.
	 *
	 * @since 1.20
	 *
	 * @param string $pathType
	 */
	public function removePath( $pathType );

}
