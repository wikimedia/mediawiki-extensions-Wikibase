<?php

namespace Wikibase\QueryEngine\SQLStore\ClaimStore;

use Wikibase\Claim;
use Wikibase\EntityId;
use Wikibase\QueryEngine\SQLStore\InternalEntityIdTransformer;
use Wikibase\Statement;

/**
 * Builder for ClaimRow objects.
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
class ClaimRowBuilder {

	protected $idFinder;

	public function __construct( InternalEntityIdTransformer $idFinder ) {
		$this->idFinder = $idFinder;
	}

	public function newClaimRow( Claim $claim, $internalSubjectId ) {
		return new ClaimRow(
			null,
			$claim->getGuid(),
			$internalSubjectId,
			$this->getInternalIdFor( $claim->getPropertyId() ),
			$claim instanceof Statement ? $claim->getRank() : 3, // TODO
			$claim->getHash()
		);
	}

	protected function getInternalIdFor( EntityId $entityId ) {
		return $this->idFinder->getInternalIdForEntity( $entityId );
	}

}