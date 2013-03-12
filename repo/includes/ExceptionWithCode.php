<?php

namespace Wikibase;

/**
 * Exception with a string error code.
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ExceptionWithCode extends \Exception {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	private $stringCode;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param string $message
	 * @param string $code
	 */
	public function __construct( $message, $code ) {
		parent::__construct( $message );
		$this->stringCode = $code;
	}

	/**
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getErrorCode() {
		return $this->stringCode;
	}

}
