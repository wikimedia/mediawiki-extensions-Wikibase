<?php

namespace Wikibase;

/**
 * Represents a change for an entity; to be extended by various change subtypes
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
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class EntityChange extends DiffChange {

	const UPDATE =  'update';
	const ADD =     'add';
	const REMOVE =  'remove';
	const RESTORE = 'restore';

	/**
	 * @var EntityId $entityId
	 */
	private $entityId = null;

	/**
	 * @var string $comment
	 */
	protected $comment;

	/**
	 * @see ORMRow::setField
	 *
	 * Overwritten to force lower case object_id
	 *
	 * @param string $name
	 * @param mixed $value
	 *
	 * @throws \MWException
	 */
	public function setField( $name, $value ) {
		if ( $name === 'object_id' && is_string( $value ) ) {
			//NOTE: for compatibility with earlier versions, use lower case IDs in the database.
			$value = strtolower( $value );
		}

		parent::setField( $name, $value );
	}

	/**
	 * @since 0.3
	 *
	 * @deprecated: as of version 0.4, no code calls setEntity(), so getEntity() will always return null.
	 *
	 * @return Entity|null
	 */
	public function getEntity() {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		if ( !array_key_exists( 'entity', $info ) ) {
			return null;
		} else {
			return $info['entity'];
		}
	}

	/**
	 * @since 0.3
	 *
	 * @note: as of version 0.4, no code calls setEntity(), so getEntity() will always return null.
	 * This is kept in the expectation that we may want to construct EntityChange objects
	 * from an atom feed or the like, where full entity data would be included and useful.
	 *
	 * @param Entity $entity
	 */
	public function setEntity( Entity $entity ) {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$info['entity'] = $entity;
		$this->setField( 'info', $info );
	}

	/**
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getType() {
		return $this->getField( 'type' );
	}

	/**
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getEntityType() {
		$id = $this->getEntityId();
		return $id->getEntityType();
	}

	/**
	 * @since 0.3
	 *
	 * @return EntityId
	 */
	public function getEntityId() {
		if ( !$this->entityId && $this->hasField( 'object_id' ) ) {
			$id = $this->getObjectId();
			$this->entityId = EntityId::newFromPrefixedId( $id );
		}

		return $this->entityId;
	}

	/**
	 *
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getAction() {
		list(, $action ) = explode( '~', $this->getType(), 2 );

		return $action;
	}

	/**
	 * @since 0.3
	 *
	 * @param string $cache set to 'cache' to cache the unserialized diff.
	 *
	 * @return array|bool
	 */
	public function getMetadata( $cache = 'no' ) {
		$info = $this->getInfo( $cache );

		if ( array_key_exists( 'metadata', $info ) ) {
			return $info['metadata'];
		}

		return false;
	}

	/**
	 * @since 0.3
	 *
	 * @param array $metadata
	 *
	 * @return bool
	 */
	public function setMetadata( array $metadata ) {
		$validKeys = array(
			'comment',
			'page_id',
			'bot',
			'rev_id',
			'parent_id',
			'user_text',
		);

		if ( is_array( $metadata ) ) {
			foreach ( array_keys( $metadata ) as $key ) {
				if ( !in_array( $key, $validKeys ) ) {
					unset( $metadata[$key] );
				}
			}

			$metadata['comment'] = $this->getComment();

			$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
			$info['metadata'] = $metadata;
			$this->setField( 'info', $info );

			return true;
		}

		return false;
	}

	/**
	 * @since 0.3
	 *
	 * @param string
	 *
	 * @return string
	 */
	public function setComment( $comment = null ) {
		if ( $comment !== null ) {
			$this->comment = $comment;
		} else {
			$this->comment = 'wikibase-comment-' . $this->getAction();
		}
	}

	/**
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getComment() {
		if ( $this->comment === null ) {
			$this->setComment();
		}
		return $this->comment;
	}

	/**
	 * @since 0.1
	 */
	protected function postConstruct() {

	}

	/**
	 * @since 0.3
	 *
	 * @param \RecentChange $rc
	 */
	public function setMetadataFromRC( \RecentChange $rc ) {
		$this->setMetadata( array(
			'user_text' => $rc->getAttribute( 'rc_user_text' ),
			'bot' => $rc->getAttribute( 'rc_bot' ),
			'page_id' => $rc->getAttribute( 'rc_cur_id' ),
			'rev_id' => $rc->getAttribute( 'rc_this_oldid' ),
			'parent_id' => $rc->getAttribute( 'rc_last_oldid' ),
			'comment' => '',
		) );
	}

	/**
	 * @since 0.3
	 *
	 * @param \User $user
	 */
	public function setMetadataFromUser( \User $user ) {
		$this->setMetadata( array(
			'user_text' => $user->getName(),
			'page_id' => 0,
			'rev_id' => 0,
			'parent_id' => 0,
			'comment' => '',
		) );
	}

	/**
	 * @since 0.3
	 *
	 * @param string $action The action name
	 * @param EntityId $entityId
	 * @param array $fields additional fields to set
	 *
	 * @return EntityChange
	 */
	protected static function newForEntity( $action, EntityId $entityId, array $fields = null ) {
		//FIXME: use factory based on $entity->getType()
		if ( $entityId->getEntityType() === Item::ENTITY_TYPE ) {
			$class = '\Wikibase\ItemChange';
		} else {
			$class = '\Wikibase\EntityChange';
		}

		/** @var EntityChange $instance  */
		$instance = new $class(
			ChangesTable::singleton(),
			$fields,
			true
		);

		if ( !$instance->hasField( 'object_id' ) ) {
			$instance->setField( 'object_id', $entityId->getPrefixedId() );
		}

		if ( !$instance->hasField( 'info' ) ) {
			$info = array();
			$instance->setField( 'info', $info );
		}

		// determines which class will be used when loading teh change from the database
		// @todo get rid of ugly cruft
		$type = 'wikibase-' . $entityId->getEntityType() . '~' . $action;
		$instance->setField( 'type', $type );

		return $instance;
	}

	/**
	 * @since 0.1
	 *
	 * @param string      $action The action name
	 * @param Entity|null $oldEntity
	 * @param Entity|null $newEntity
	 * @param array|null  $fields additional fields to set
	 *
	 * @return EntityChange
	 * @throws \MWException
	 */
	public static function newFromUpdate( $action, Entity $oldEntity = null, Entity $newEntity = null, array $fields = null ) {
		if ( $oldEntity === null && $newEntity === null ) {
			throw new \MWException( 'Either $oldEntity or $newEntity must be give.' );
		}

		if ( $oldEntity === null ) {
			$oldEntity = EntityFactory::singleton()->newEmpty( $newEntity->getType() );
			$theEntity = $newEntity;
		} elseif ( $newEntity === null ) {
			$newEntity = EntityFactory::singleton()->newEmpty( $oldEntity->getType() );
			$theEntity = $oldEntity;
		} elseif ( $oldEntity->getType() !== $newEntity->getType() ) {
			throw new \MWException( 'Entity type mismatch' );
		} else {
			$theEntity = $newEntity;
		}

		/**
		 * @var EntityChange $instance
		 */
		$diff = $oldEntity->getDiff( $newEntity );
		$instance = self::newForEntity( $action, $theEntity->getId(), $fields );
		$instance->setDiff( $diff );
		$instance->setEntity( $theEntity );

		return $instance;
	}

	/**
	 * Returns a human readable string representation of the change. Useful for logging and debugging.
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function __toString() {
		$s = get_class( $this );
		$s .= ": ";

		$fields = $this->getFields();
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$meta = $this->getMetadata();

		if ( is_array( $info ) ) {
			$fields = array_merge( $fields, $info );
		}

		if ( is_array( $meta ) ) {
			$fields = array_merge( $fields, $meta );
		}

		foreach ( $fields as $k => $v ) {
			if ( is_array( $v ) || is_object( $v ) ) {
				unset( $fields[$k] );
			}
		}

		ksort( $fields );

		$s .= preg_replace( '/\s+/s', ' ', var_export( $fields, true ) );
		return $s;
	}

	/**
	 * @see DiffChange::arrayalizeObjects
	 *
	 * Overwritten to handle Claim objects.
	 *
	 * @since 0.4
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function arrayalizeObjects( $data ) {
		$data = parent::arrayalizeObjects( $data );

		if ( $data instanceof Claim ) {
			$a = $data->toArray();
			$a['_claimclass_'] = get_class( $data );

			return $a;
		}

		return $data;
	}

	/**
	 * @see DiffChange::objectifyArrays
	 *
	 * Overwritten to handle Claim objects.
	 *
	 * @since 0.4
	 *
	 * @param array $data
	 * @return mixed
	 */
	public function objectifyArrays( array $data ) {
		$data = parent::objectifyArrays( $data );

		if ( is_array( $data ) && isset( $data['_claimclass_'] ) ) {
			$class = $data['_claimclass_'];

			if ( $class === 'Wikibase\Claim' || is_subclass_of( $class, 'Wikibase\Claim' ) ) {
				unset( $data['_claimclass_'] );

				$claim = call_user_func( array( $class, 'newFromArray' ), $data );
				return $claim;
			}
		}

		return $data;
	}

	/**
	 * @see ChangeRow::serializeInfo()
	 *
	 * Overwritten to use the array representation of the diff.
	 *
	 * @since 0.4
	 * @param array $info
	 * @return string
	 */
	public function serializeInfo( array $info ) {
		if ( isset( $info['entity'] ) ) {
			// never serialize full entity data in a change, it's huge.
			unset( $info['entity'] );
		}

		return parent::serializeInfo( $info );
	}
}
