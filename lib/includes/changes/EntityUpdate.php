<?php

namespace Wikibase;

/**
 * Class representing an update to an entity.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityUpdate extends DiffChange {

	/**
	 * @since 0.1
	 *
	 * @return Entity
	 * @throws \MWException
	 */
	public function getEntity() {
		$info = $this->getField( 'info' );

		if ( !array_key_exists( 'entity', $info ) ) {
			throw new \MWException( 'Cannot get the entity when it has not been set yet.' );
		}

		return $info['entity'];
	}

	/**
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function setEntity( Entity $entity ) {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$info['entity'] = $entity;
		$this->setField( 'info', $info );
	}

	/**
	 * @since 0.1
	 *
	 * @param Entity $oldEntity
	 * @param Entity $newEntity
	 *
	 * @return EntityUpdate
	 * @throws \MWException
	 */
	public static function newFromEntities( Entity $oldEntity, Entity $newEntity ) {
		$type = $oldEntity->getType();

		if ( $type !== $newEntity->getType() ) {
			throw new \MWException( 'Entity type mismatch' );
		}

		$typeMap = array(
			Item::ENTITY_TYPE => '\Wikibase\EntityUpdate',
			Property::ENTITY_TYPE => '\Wikibase\EntityUpdate',
			Query::ENTITY_TYPE => '\Wikibase\EntityUpdate',
		);

		/**
		 * @var EntityUpdate $instance
		 */
		$instance = new $typeMap[$type](
			ChangesTable::singleton(),
			array(),
			true
		);

		$instance->setEntity( $newEntity );
		$instance->setField( 'type', $instance->getType() );
		$instance->setDiff( $oldEntity->getDiff( $newEntity ) );

		return $instance;
	}

	/**
	 * @see ChangeRow::postConstruct
	 *
	 * @since 0.1
	 */
	protected function postConstruct() {
	}

	/**
	 * @see Change::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public final function getType() {
		return $this->getEntity()->getType() . '~update';
	}

}
