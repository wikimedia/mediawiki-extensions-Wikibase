<?php

namespace Wikibase;
use MWException;

class EntityPatcher extends \Diff\MapPatcher {

	/**
	 * @since 0.4
	 *
	 * @param string $entityType
	 *
	 * @return EntityDiffer
	 */
	public static function newForType( $entityType ) {
		return new static( true );
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
		$mapPatcher = new \Diff\MapPatcher();


		return $entity;
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected function entityFromArray( Entity $entity ) {
		$array = array();

		$array['aliases'] = $entity->getAllAliases();
		$array['label'] = $entity->getLabels();
		$array['description'] = $entity->getDescriptions();

		// TODO: claims

		return $array;
	}

}