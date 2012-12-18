<?php

namespace Wikibase;
use MWException;

class EntityDiffer extends \Diff\MapDiffer {

	/**
	 * @since 0.4
	 *
	 * @param string $entityType
	 *
	 * @return EntityDiffer
	 */
	public static function newForType( $entityType ) {
		if ( $entityType === Item::ENTITY_TYPE ) {
			$class = '\Wikibase\ItemDiffer';
		}
		else {
			$class = __CLASS__;
		}

		return new $class( true );
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $oldEntity
	 * @param Entity $newEntity
	 *
	 * @return EntityDiff
	 * @throws MWException
	 */
	public final function diffEntities( Entity $oldEntity, Entity $newEntity ) {
		if ( $oldEntity->getType() !== $newEntity->getType() ) {
			throw new MWException( 'Can only diff between entities of the same type' );
		}

		$entityType = $oldEntity->getType();

		$oldEntity = $this->entityToArray( $oldEntity );
		$newEntity = $this->entityToArray( $newEntity );

		$diffOps = $this->doDiff( $oldEntity, $newEntity );
		$diff = EntityDiff::newForType( $entityType, $diffOps );

		return $diff;
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected function entityToArray( Entity $entity ) {
		$array = array();

		$array['aliases'] = $entity->getAllAliases();
		$array['label'] = $entity->getLabels();
		$array['description'] = $entity->getDescriptions();

		// TODO: claims

		return $array;
	}

}