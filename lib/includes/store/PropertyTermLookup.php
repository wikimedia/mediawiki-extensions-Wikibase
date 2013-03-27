<?php

namespace Wikibase;

/**
 * Implementation of PropertyLookup based on a TermIndex
 *
 * @todo add caching (LRU in memcached?)
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
 * @author Daniel Kinzler
 */
class PropertyTermLookup implements PropertyLookup {

	/**
	 * @var TermIndex
	 **/
	protected $termIndex;

	/**
	 * @var array[] An array of arrays of item IDs:
	 *          $propertiesByLabel[$lang][$label] = $propertyId.
	 **/
	protected $propertiesByLabel;

	/**
	 * @since 0.4
	 *
	 * @param TermIndex $termIndex
	 */
	public function __construct( TermIndex $termIndex ) {
		$this->termIndex = $termIndex;
	}

	/**
	 * Fetches the labels for the given properties from the TermIndex
	 * and caches them in $this->propertiesByLabel[$lang].
	 *
	 * @param EntityId[] $propertyIds
	 * @param string $lang
	 */
	protected function prefetchLabels( array $propertyIds, $lang ) {
		$terms = $this->termIndex->getTermsOfEntities( $propertyIds, Property::ENTITY_TYPE, $lang );

		if ( !isset( $this->propertiesByLabel[$lang] ) ) {
			$this->propertiesByLabel[$lang] = array();
		}

		/* @var Term $term */
		foreach ( $terms as $term ) {
			if ( $term->getType() === Term::TYPE_LABEL ) {
				$label = $term->getText();
				$this->propertiesByLabel[$lang][$label] = $term->getEntityId();
			}
		}
	}

	/**
	 * @since 0.4
	 *
	 * @param string $propertyLabel
	 * @param string $langCode
	 *
	 * @return int|null|bool The property's integer ID, or false of known to be undefined,
	 *          or null if not yet loaded.
	 */
	protected function getCachedPropertyId( $propertyLabel, $langCode ) {
		if ( isset( $this->propertiesByLabel[$langCode][$propertyLabel] ) ) {
			return $this->propertiesByLabel[$langCode][$propertyLabel];
		}

		return null;
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param string $propertyLabel
	 * @param string $langCode
	 *
	 * @return Claims
	 */
	public function getClaimsByPropertyLabel( Entity $entity, $propertyLabel, $langCode ) {
		wfProfileIn( __METHOD__ );

		$allClaims = new Claims( $entity->getClaims() );

		$propertyId = $this->getCachedPropertyId( $propertyLabel, $langCode );

		if ( $propertyId === null ) {
			//NOTE: No negative caching. If we are looking up a label that can't be found
			//      in the item, we'll always try to re-index the item's properties.
			//      We just hope that this is rare, because people notice when a label
			//      doesn't work.

			$propertyIds = $allClaims->getMainSnaks()->getPropertyIds();

			$this->prefetchLabels( $propertyIds, $langCode );
			$propertyId = $this->getCachedPropertyId( $propertyLabel, $langCode );
		}

		if ( $propertyId !== null && $propertyId !== false ) {
			$claims = $allClaims->getClaimsForProperty( $propertyId );
		} else {
			$claims = new Claims();
		}

		wfProfileOut( __METHOD__ );
		return $claims;
	}

}
