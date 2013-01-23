<?php

namespace Wikibase;

/**
 * Parser error object
 *
 * @todo is there nothing like this in core? if not, move to core
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
class ParserError {

	protected $messageKey;

	protected $messageParams;

	/**
	 * @since 0.4
	 *
	 * @param string $key
	 * @param $params[]
	 */
	public function __construct( $key, array $params = array() ) {
		$this->messageKey = $key;
		$this->messageParams = $params;
	}

	/**
	 * Formats an error message
	 * @todo is there really nothing like this function in core?
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function output() {
		return \Html::rawElement(
			'span',
			array( 'class' => 'error' ),
            wfMessage( $this->messageKey, $this->messageParams )->text()
		);
	}

}
