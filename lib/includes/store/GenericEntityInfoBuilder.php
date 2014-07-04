<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;

/**
 * EntityInfoBuilder based on an EntityLookup.
 *
 * This is a rather inefficient implementation of EntityInfoBuilder, intended
 * mainly for testing.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class GenericEntityInfoBuilder implements EntityInfoBuilder {

	/**
	 * @var array[]
	 */
	private $entityInfo;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @param EntityId[] $ids
	 * @param EntityIdParser $idParser
	 * @param EntityLookup $entityLookup
	 */
	public function __construct( array $ids, EntityIdParser $idParser, EntityLookup $entityLookup ) {
		$this->idParser = $idParser;
		$this->entityLookup = $entityLookup;

		$this->setEntityIds( $ids );
	}

	/**
	 * @param EntityId[] $ids
	 */
	private function setEntityIds( $ids ) {
		$this->entityInfo = array();

		foreach ( $ids as $id ) {
			$key = $id->getSerialization();
			$type = $id->getEntityType();

			$this->entityInfo[$key] = array(
				'id' => $key,
				'type' => $type,
			);
		}
	}

	private function parseId( $id ) {
		return $this->idParser->parse( $id );
	}

	private function getEntity( EntityId $id ) {
		return $this->entityLookup->getEntity( $id );
	}

	/**
	 * @see EntityInfoBuilder::collectTerms
	 *
	 * @param string[]|null $types Which types of terms to include (e.g. "label", "description", "aliases").
	 * @param string[]|null $languages Which languages to include
	 */
	public function collectTerms( array $types = null, array $languages = null ) {
		foreach ( $this->entityInfo as $id => &$entityRecord ) {
			$id = $this->parseId( $id );
			$entity = $this->getEntity( $id );

			if ( !$entity ) {
				// hack: fake an empty entity, so the field get initialized
				$entity = Item::newEmpty();
			}

			if ( $types === null || in_array( 'label', $types ) ) {
				$this->injectLabels( $entityRecord, $entity, $languages );
			}

			if ( $types === null || in_array( 'description', $types ) ) {
				$this->injectDescriptions( $entityRecord, $entity, $languages );
			}

			if ( $types === null || in_array( 'alias', $types ) ) {
				$this->injectAliases( $entityRecord, $entity, $languages );
			}
		}
	}

	private function injectLabels( array &$entityRecord, Entity $entity, $languages ) {
		$labels = $entity->getLabels( $languages );

		if ( !isset( $entityRecord['labels'] ) ) {
			$entityRecord['labels'] = array();
		}

		foreach ( $labels as $lang => $text ) {
			$entityRecord['labels'][$lang] = array(
				'language' => $lang,
				'value' => $text,
			);
		}
	}

	private function injectDescriptions( array &$entityRecord, Entity $entity, $languages ) {
		$descriptions = $entity->getDescriptions( $languages );

		if ( !isset( $entityRecord['descriptions'] ) ) {
			$entityRecord['descriptions'] = array();
		}

		foreach ( $descriptions as $lang => $text ) {
			$entityRecord['descriptions'][$lang] = array(
				'language' => $lang,
				'value' => $text,
			);
		}
	}

	private function injectAliases( array &$entityRecord, Entity $entity, $languages ) {
		if ( $languages === null ) {
			$languages = array_keys( $entity->getAllAliases() );
		}

		if ( !isset( $entityRecord['aliases'] ) ) {
			$entityRecord['aliases'] = array();
		}

		foreach ( $languages as $lang ) {
			$aliases = $entity->getAliases( $lang );
			$entityRecord['aliases'][$lang] = array();

			foreach ( $aliases as $text ) {
				$entityRecord['aliases'][$lang][] = array( // note: append
					'language' => $lang,
					'value' => $text,
				);
			}
		}
	}

	/**
	 * @see EntityInfoBuilder::collectDataTypes
	 */
	public function collectDataTypes() {
		foreach ( $this->entityInfo as $id => &$entityRecord ) {
			$id = $this->parseId( $id );

			if ( $id->getEntityType() !== Property::ENTITY_TYPE ) {
				continue;
			}

			$entity = $this->getEntity( $id );

			if ( $entity instanceof Property ) {
				$entityRecord['datatype'] = $entity->getDataTypeId();
			} else {
				$entityRecord['datatype'] = null;
			}
		}
	}

	/**
	 * @see EntityInfoBuilder::removeMissing
	 */
	public function removeMissing() {
		foreach ( array_keys( $this->entityInfo ) as $key ) {
			$id = $this->parseId( $key );
			$entity = $this->getEntity( $id );

			if ( !$entity ) {
				unset( $this->entityInfo[$key] );
			}
		}
	}

	/**
	 * @see EntityInfoBuilder::getEntityInfo
	 *
	 * @return array[]
	 */
	public function getEntityInfo() {
		return $this->entityInfo;
	}

}
