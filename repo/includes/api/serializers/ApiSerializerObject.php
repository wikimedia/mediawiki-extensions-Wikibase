<?php

namespace Wikibase;
use ApiResult;

/**
 * Base class for ApiSerializers.
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
abstract class ApiSerializerObject implements ApiSerializer {

	/**
	 * The options to use during serialization.
	 *
	 * @since 0.2
	 *
	 * @var ApiResult
	 */
	private $apiResult;

	/**
	 * The ApiResult to use during serialization.
	 *
	 * @since 0.2
	 *
	 * @var ApiSerializationOptions|null
	 */
	protected $options;

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 *
	 * @param ApiResult $apiResult
	 * @param ApiSerializationOptions $options
	 */
	public function __construct( ApiResult $apiResult, ApiSerializationOptions $options = null ) {
		$this->apiResult = $apiResult;
		$this->options = $options;
	}

	/**
	 * @see ApiSerializer::setOptions
	 *
	 * @since 0.2
	 *
	 * @param ApiSerializationOptions $options
	 */
	public final function setOptions( ApiSerializationOptions $options ) {
		$this->options = $options;
	}

	/**
	 * @see ApiSerializer::setApiResult
	 *
	 * @since 0.2
	 *
	 * @param ApiResult $apiResult
	 */
	public final function setApiResult( ApiResult $apiResult ) {
		$this->apiResult = $apiResult;
	}

	/**
	 * Returns the ApiResult to use during serialization.
	 *
	 * @since 0.2
	 *
	 * @return ApiResult
	 */
	protected final function getResult() {
		return $this->apiResult;
	}

	/**
	 * Returns the ApiResult to use during serialization.
	 *
	 * @since 0.2
	 *
	 * @return ApiSerializationOptions
	 */
	protected final function getOptions() {
		if ( $this->options === null ) {
			$this->options = new ApiSerializationOptions();
		}

		return $this->options;
	}

}