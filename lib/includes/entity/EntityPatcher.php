<?php

namespace Wikibase;
use MWException;
use Diff\MapPatcher;

class EntityPatcher {

	/**
	 * @since 0.4
	 *
	 * @param string $entityType
	 *
	 * @return EntityDiffer
	 */
	public static function newForType( $entityType ) {
		if ( $entityType === Item::ENTITY_TYPE ) {
			$class = '\Wikibase\ItemPatcher';
		}
		else {
			$class = __CLASS__;
		}

		return new $class( new MapPatcher() );
	}

	/**
	 * @since 0.4
	 *
	 * @var MapPatcher
	 */
	protected $mapPatcher;

	/**
	 * @since 0.4
	 *
	 * @param boolean $throwErrors
	 */
	public function __construct( MapPatcher $mapPatcher ) {
		$this->mapPatcher = $mapPatcher;
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $oldEntity
	 * @param EntityDiff $patch
	 *
	 * @return Entity
	 * @throws MWException
	 */
	public final function getPatchedEntity( Entity $entity, EntityDiff $patch ) {
		$entity = $entity->copy();

		$entity->setLabels( $this->mapPatcher->patch( $entity->getLabels(), $patch->getLabelsDiff() ) );
		$entity->setDescriptions( $this->mapPatcher->patch( $entity->getDescriptions(), $patch->getDescriptionsDiff() ) );
		$entity->setAllAliases( $this->mapPatcher->patch( $entity->getAllAliases(), $patch->getAliasesDiff() ) );

		$this->patchSpecificFields( $entity, $patch );

		return $entity;
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param EntityDiff $patch
	 */
	protected function patchSpecificFields( Entity &$entity, EntityDiff $patch ) {
		// No-op, meant to be overridden in deriving classes to add specific behaviour
	}

}