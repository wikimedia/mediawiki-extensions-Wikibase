<?php

namespace Wikibase;
use ApiResult, MWException;

/**
 * Interface for serializers that take an object and transform it into API output.
 * Note: the term "serialize" is use loosely here. Internal objects are turned into API structures.
 *
 * If an unserializer is available, you can roundtrip via ApiUnserializer::getUnserialized
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
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface ApiSerializer {

	/**
	 * Turns the provided object to API output and returns this serialization.
	 *
	 * @since 0.2
	 *
	 * @param mixed $object
	 *
	 * @return array
	 */
	public function getSerialized( $object );

	/**
	 * Sets the options to use during serialization.
	 *
	 * @since 0.2
	 *
	 * @param ApiSerializationOptions $options
	 */
	public function setOptions( ApiSerializationOptions $options );

	/**
	 * Sets the ApiResult to use during serialization.
	 *
	 * @since 0.2
	 *
	 * @param ApiResult $apiResult
	 */
	public function setApiResult( ApiResult $apiResult );

}
