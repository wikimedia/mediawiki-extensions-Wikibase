<?php

namespace Wikibase\QueryEngine\SQLStore\ClaimStore;

/**
 * Represents a row in the claims table.
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
 * @ingroup WikibaseSQLStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimRow {

	protected $internalId;
	protected $externalGuid;
	protected $internalSubjectId;
	protected $internalPropertyId;
	protected $rank;
	protected $hash;

	/**
	 * @param int|null $internalId
	 * @param string $externalGuid
	 * @param int $internalSubjectId
	 * @param int $internalPropertyId
	 * @param int $rank
	 * @param string $hash
	 */
	public function __construct( $internalId, $externalGuid, $internalSubjectId, $internalPropertyId, $rank, $hash ) {
		$this->internalId = $internalId;
		$this->externalGuid = $externalGuid;
		$this->internalSubjectId = $internalSubjectId;
		$this->internalPropertyId = $internalPropertyId;
		$this->rank = $rank;
		$this->hash = $hash;
	}

	/**
	 * @return int|null
	 */
	public function getInternalId() {
		return $this->internalId;
	}

	/**
	 * @return string
	 */
	public function getExternalGuid() {
		return $this->externalGuid;
	}

	/**
	 * @return int
	 */
	public function getInternalSubjectId() {
		return $this->internalSubjectId;
	}

	/**
	 * @return int
	 */
	public function getInternalPropertyId() {
		return $this->internalPropertyId;
	}

	/**
	 * @return int
	 */
	public function getRank() {
		return $this->rank;
	}

	/**
	 * @return string
	 */
	public function getHash() {
		return $this->hash;
	}

}
