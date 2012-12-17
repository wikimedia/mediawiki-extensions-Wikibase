<?php

namespace Wikibase;

/**
 * Finds linked entities given a list of entities or a list of claims.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferencedEntitiesFinder {

	/**
	 * @since 0.4
	 *
	 * @var EntityLookup
	 */
	protected $entityLoader;

	/**
	 * @since 0.4
	 *
	 * @param EntityLookup $entityLoader
	 */
	public function __construct( EntityLookup $entityLoader ) {
		$this->entityLoader = $entityLoader;
	}

	/**
	 * @since 0.4
	 *
	 * @param Claims $claims
	 *
	 * @return EntityId[]
	 */
	public function findClaimLinks( Claims $claims ) {
		$snaks = array();

		/**
		 * @var Claim $claim
		 */
		foreach ( $claims as $claim ) {
			$snaks[] = $claim->getMainSnak();
			$snaks = array_merge( $snaks, iterator_to_array( $claim->getQualifiers() ) );
		}

		return $this->findSnakLinks( $snaks );
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return EntityId[]
	 */
	protected function findSnakLinks( array $snaks ) {
		$foundEntities = array();
		$propertyIds = array();

		foreach ( $snaks as $snak ) {
			$foundEntities[] = $snak->getPropertyId();

			if ( $snak instanceof PropertyValueSnak ) {
				$propertyIds[] = $snak->getPropertyId();
			}
		}

		$propertyIds = array_unique( $propertyIds );

		$properties = $this->entityLoader->getEntities( $propertyIds );

		foreach ( $snaks as $snak ) {
			if ( $snak instanceof PropertyValueSnak ) {
				$prefixedId = $snak->getPropertyId()->getPrefixedId();

				if ( array_key_exists( $prefixedId, $properties ) ) {
					$dataType = $properties[$prefixedId]->getDataType()->getId();

					if ( $dataType === 'wikibase-item' ) {
						$entityId = EntityId::newFromPrefixedId( $snak->getDataValue()->getValue() );

						if ( $entityId === null ) {
							// TODO: handle ref to non-existing item
						}
						else {
							$foundEntities[] = $entityId;
						}
					}
				}
				else {
					// TODO: handle ref to non-existing property
				}
			}
		}

		return array_unique( $foundEntities );
	}

}


