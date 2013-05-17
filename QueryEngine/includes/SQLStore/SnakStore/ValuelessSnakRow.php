<?php

namespace Wikibase\QueryEngine\SQLStore\SnakStore;

use InvalidArgumentException;

/**
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
 * @ingroup WikibaseSQLStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ValuelessSnakRow extends SnakRow {

	const TYPE_NO_VALUE = 0;
	const TYPE_SOME_VALUE = 1;

	protected $internalSnakType;

	/**
	 * @param int $internalSnakType
	 * @param int $internalPropertyId
	 * @param int $internalClaimId
	 * @param int $snakRole
	 * @param int $internalSubjectId
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $internalSnakType, $internalPropertyId, $internalClaimId, $snakRole, $internalSubjectId ) {
		if ( !in_array( $internalSnakType, array( self::TYPE_NO_VALUE, self::TYPE_SOME_VALUE ), true ) ) {
			throw new InvalidArgumentException( 'Invalid internal snak type provided' );
		}

		parent::__construct( $internalPropertyId, $internalClaimId, $snakRole, $internalSubjectId );

		$this->internalSnakType = $internalSnakType;
	}

	/**
	 * @return int
	 */
	public function getInternalSnakType() {
		return $this->internalSnakType;
	}

}
