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
	 * The entity info data structure. This data structure is exposed via getEntityInfo().
	 * After resolveRedirects() is called, this will contain entries for the redirect targets
	 * in addition to the entries for the redirected IDs. Entries for the redirected IDs
	 * will be php references to the entries that use the actual (target) IDs as keys.
	 *
	 * @see EntityInfoBuilder::getEntityInfo()
	 *
	 * @var array[] map of id-strings to entity record arrays:
	 *      id-string => entity-record
	 */
	private $entityInfo = null;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * A map of entity id strings to EntityId objects, representing any
	 * redirects present in the list of entities provided to the constructor.
	 *
	 * Initialized lazily by resolveRedirects().
	 *
	 * @var string[] map of id-strings to EntityId objects:
	 *      id-string => EntityId
	 */
	private $redirects = null;

	/**
	 * @param EntityId[] $ids
	 * @param EntityIdParser $idParser
	 * @param EntityRevisionLookup $entityRevisionLookup
	 */
	public function __construct( array $ids, EntityIdParser $idParser, EntityRevisionLookup $entityRevisionLookup ) {
		$this->idParser = $idParser;
		$this->entityRevisionLookup = $entityRevisionLookup;

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
		try {
			$rev = $this->entityRevisionLookup->getEntityRevision( $id );
			return $rev === null ? null : $rev->getEntity();
		} catch ( UnresolvedRedirectException $ex ) {
			return null;
		}
	}

	private function getRedirect( EntityId $id ) {
		try {
			$this->entityRevisionLookup->getEntityRevision( $id );
			return null;
		} catch ( UnresolvedRedirectException $ex ) {
			return $ex->getRedirectTargetId();
		}
	}

	/**
	 * @see EntityInfoBuilder::resolveRedirects
	 */
	public function resolveRedirects() {
		$ids = array_keys( $this->entityInfo );

		$this->redirects = array();

		foreach ( $ids as $idString ) {
			$id = $this->parseId( $idString );
			$targetId = $this->getRedirect( $id );

			if ( $targetId ) {
				$this->redirects[$idString] = $targetId;
				$this->applyRedirect( $idString, $targetId );
			}
		}
	}

	/**
	 * Applied the given redirect to the internal data structure
	 *
	 * @param string $idString The redirected entity id
	 * @param EntityId $targetId The redirect target
	 */
	private function applyRedirect( $idString, EntityId $targetId) {
		$targetKey = $targetId->getSerialization();

		if ( $idString === $targetKey ) {
			// Sanity check: self-redirect, nothing to do.
			return;
		}

		// If the redirect target doesn't have a record yet, copy the old record.
		// Since two IDs may be redirected to the same target, this may already have
		// happened.
		if ( !isset( $this->entityInfo[$targetKey] ) ) {
			$this->entityInfo[$targetKey] = $this->entityInfo[$idString]; // copy
			$this->entityInfo[$targetKey]['id'] = $targetKey; // update id
		}

		// Make the redirected key a reference to the target record.
		unset( $this->entityInfo[$idString] ); // just to be sure not to cause a mess
		$this->entityInfo[$idString] = & $this->entityInfo[$targetKey];
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
	public function removeMissing( $redirects = 'keep-redirects' ) {
		foreach ( array_keys( $this->entityInfo ) as $key ) {
			$id = $this->parseId( $key );

			try {
				$rev = $this->entityRevisionLookup->getEntityRevision( $id );
			} catch ( UnresolvedRedirectException $ex ) {
				if ( $redirects === 'keep-redirects' ) {
					continue;
				} else {
					$rev = null;
				}
			}

			if ( !$rev ) {
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

	/**
	 * @param EntityId[] $ids
	 *
	 * @return string[]
	 */
	private function convertEntityIdsToStrings( array $ids ) {
		return array_map( function ( EntityId $id ) {
			return $id->getSerialization();
		}, $ids );
	}

	/**
	 * Remove info records for the given EntityIds.
	 *
	 * @param EntityId[] $ids
	 */
	public function removeEntityInfo( array $ids ) {
		$remove = $this->convertEntityIdsToStrings( $ids );
		$this->entityInfo = array_diff_key( $this->entityInfo, array_flip( $remove ) );
	}

	/**
	 * Retain only info records for the given EntityIds.
	 * Useful e.g. after resolveRedirects(), to remove explicit entries for
	 * redirect targets not present in the original input.
	 *
	 * @param EntityId[] $ids
	 */
	public function retainEntityInfo( array $ids ) {
		$retain = $this->convertEntityIdsToStrings( $ids );
		$this->entityInfo = array_intersect_key( $this->entityInfo, array_flip( $retain ) );
	}

}
