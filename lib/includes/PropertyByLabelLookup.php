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
class PropertyByLabelLookup {

	/* @var Language */
	protected $language;

	/* @var EntityId */
	protected $entityId;

	/* @var WikiPageEntityLookup */
	protected $entityLookup;

	/* @var $propertyIdLabelArray */
	protected $propertyIdLabelArray;

	/* @var array */
	protected $statementsByProperty;

	/**
	 * @since 0.4
	 *
	 * @param \Language $language
	 * @param EntityId $entityId
	 * @param WikiPageEntityLookup $entityLookup
	 */
	public function __construct( \Language $language, EntityId $entityId, WikiPageEntityLookup $entityLookup ) {
		$this->language = $language;
		$this->entityId = $entityId;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $entity
	 */
	public function indexProperties( Entity $entity ) {
		wfProfileIn( __METHOD__ );

		$langCode = $this->language->getCode();
		$propertyArray = new PropertyIdLabelArray( $langCode );
		$statementsByProperty = array();

		foreach( $entity->getClaims() as $statement ) {
			$propertyId = $statement->getMainSnak()->getPropertyId();

			if ( $propertyId === null ) {
				continue;
			}

			$statementsByProperty[$propertyId->getNumericId()][] = $statement;

			$property = $this->entityLookup->getEntity( $propertyId );
			$propertyLabel = $property->getLabel( $langCode );

			if ( $propertyLabel !== false ) {
				$propertyArray->setProperty(
					$propertyId->getNumericId(),
					$propertyLabel
				);
			}
		}

		$this->statementsByProperty = $statementsByProperty;
		$this->propertyIdLabelArray = $propertyArray;

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $propertyId
	 *
	 * @return string|false
	 */
	public function getPropertyLabel( EntityId $propertyId ) {
		wfProfileIn( __METHOD__ );
		$property = $this->entityLookup->getEntity( $propertyId );
		$propertyLabel = $property->getLabel( $this->language->getCode() );

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
	 * @param EntityId $entityId
	 * @param string $propertyLabel
 	 *
	 * @return SnakList
	 */
	public function getSnaksByPropertyLabel( EntityId $entityId, $propertyLabel ) {
        wfProfileIn( __METHOD__ );

		if ( $this->propertyIdLabelArray === null ) {
			$entity = $this->entityLookup->getEntity( $entityId );
			$this->indexProperties( $entity );
		}

		$propertyIds = $this->propertyIdLabelArray->getByLabel( $propertyLabel );

		if ( ! $propertyIds ) {
			// nothing found, return empty
			$snakList = new SnakList();
		} else if ( count( $propertyIds ) > 1 ) {
			// @todo handle error! should not be duplicates, return empty
			$snakList = new SnakList();
		} else {
			$propertyId = new EntityId( Property::ENTITY_TYPE, $propertyIds[0] );
			$statements = $this->getStatementsByProperty( $propertyId );
			$snakList = new SnakList();

			foreach( $statements as $statement ) {
				$snakList->addSnak( $statement->getMainSnak() );
			}
		}

		wfProfileOut( __METHOD__ );
		return $snakList;
	}

}
