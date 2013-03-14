<?php

namespace Wikibase;

/**
 * Property lookup by label
 *
 * @todo use terms table to do lookups, add caching and tests
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
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyByLabelLookup extends PropertyByIdLookup {

	/* @var EntityLookup */
	protected $entityLookup;

	/* @var array */
	protected $statementsByProperty;

	/* @var array */
	protected $propertiesByLabel;

	/**
	 * @since 0.4
	 *
	 * @param EntityLookup $entityLookup
	 */
	public function __construct( EntityLookup $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 */
	public function indexProperties( EntityId $entityId, $langCode ) {
		wfProfileIn( __METHOD__ );

		$propertyList = array();
		$statementsByProperty = array();

		$entity = $this->entityLookup->getEntity( $entityId );

		foreach( $entity->getClaims() as $statement ) {
			$propertyId = $statement->getMainSnak()->getPropertyId();

			if ( $propertyId === null ) {
				continue;
			}

			$statementsByProperty[$propertyId->getNumericId()][] = $statement;

			$property = $this->entityLookup->getEntity( $propertyId );
			$propertyLabel = $property->getLabel( $langCode );

			if ( $propertyLabel !== false ) {
				$id = $property->getPrefixedId();
				$propertyList[$id] = $propertyLabel;
			}
		}

		$this->propertiesByLabel[$langCode] = $propertyList;
		$this->statementsByProperty = $statementsByProperty;

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $propertyId
	 *
	 * @return string|false
	 */
	public function getPropertyLabel( EntityId $propertyId, $langCode ) {
		wfProfileIn( __METHOD__ );
		$property = $this->entityLookup->getEntity( $propertyId );
		$propertyLabel = $property->getLabel( $langCode );

		wfProfileOut( __METHOD__ );
		return $propertyLabel;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $propertyId
	 *
	 * @return Statement[]
	 */
	public function getStatementsByProperty( EntityId $propertyId ) {
		wfProfileIn( __METHOD__ );
		$numericId = $propertyId->getNumericId();

		$statements = array_key_exists( $numericId, $this->statementsByProperty )
			? $this->statementsByProperty[$numericId] : array();

		wfProfileOut( __METHOD__ );
		return $statements;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $propertyId
	 *
	 * @return SnakList
	 */
	protected function getSnakListForProperty( EntityId $propertyId ) {
		wfProfileIn( __METHOD__ );
		$statements = $this->getStatementsByProperty( $propertyId );
		$snakList = new SnakList();

		foreach( $statements as $statement ) {
			$snakList->addSnak( $statement->getMainSnak() );
		}

		wfProfileOut( __METHOD__ );
		return $snakList;
	}

	/**
	 * @since 0.4
	 *
	 * @param string $propertyLabel
	 * @param string $langCode
	 *
	 * @return EntityId|null
	 */
	protected function getPropertyIdByLabel( $propertyLabel, $langCode ) {
		wfProfileIn( __METHOD__ );
		$propertyId = array_search( $propertyLabel, $this->propertiesByLabel[$langCode] );
		if ( $propertyId !== false ) {
			$entityId = EntityId::newFromPrefixedId( $propertyId );

			wfProfileOut( __METHOD__ );
			return $entityId;
		}

		wfProfileOut( __METHOD__ );
		return null;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 * @param string $propertyLabel
	 * @param string $langCode
	 *
	 * @return SnakList
	 */
	public function getMainSnaksByPropertyLabel( EntityId $entityId, $propertyLabel, $langCode ) {
        wfProfileIn( __METHOD__ );

		if ( $this->propertiesByLabel === null ) {
			$this->indexProperties( $entityId, $langCode );
		}

		$propertyId = $this->getPropertyIdByLabel( $propertyLabel, $langCode );
		$snakList = $propertyId !== null ? $this->getSnakListForProperty( $propertyId ) : new SnakList();

		wfProfileOut( __METHOD__ );
		return $snakList;
	}

}
