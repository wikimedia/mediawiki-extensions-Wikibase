<?php

namespace Wikibase;
use MWException;

/**
 * Interface for objects that can transform variables of a certain type into an array
 * of primitive value or nested arrays. This output can then be fed to a serialization
 * function such as json_encode() or serialize(). The format used is suitable for
 * exposure to the outside world, so can be used in APIs, be put into pages as
 * JSON blobs to be used by widgets or by an exporter. The formats are not optimized
 * for conciseness and can contain a lot of redundant info, and are thus often not
 * ideal for serialization for internal storage.
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
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Serializer {

	/**
	 * Turns the provided object to API output and returns this serialization.
	 *
	 * @since 0.3
	 *
	 * @param mixed $object
	 *
	 * @return array
	 */
	public function getSerialized( $object );

	/**
	 * Sets the options to use during serialization.
	 *
	 * @since 0.3
	 *
	 * @param SerializationOptions $options
	 */
	public function setOptions( SerializationOptions $options );

	/**
	 * Returns the ApiResult to use during serialization.
	 * Modification of options via this getter is allowed.
	 *
	 * @since 0.3
	 *
	 * @return SerializationOptions
	 */
	public function getOptions();

}

