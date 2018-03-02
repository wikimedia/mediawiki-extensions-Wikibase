<?php

namespace Wikibase;

use MWException;
use RecentChange;
use Revision;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\Changes\EntityDiffChangedAspects;

/**
 * Represents a change for an entity; to be extended by various change subtypes
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Matthew Flaschen < mflaschen@wikimedia.org >
 */
class EntityChange extends DiffChange {

	const UPDATE = 'update';
	const ADD = 'add';
	const REMOVE = 'remove';
	const RESTORE = 'restore';

	/**
	 * @var EntityId|null
	 */
	private $entityId = null;

	/**
	 * @return string
	 */
	public function getType() {
		return $this->getField( 'type' );
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		if ( !$this->entityId && $this->hasField( 'object_id' ) ) {
			// FIXME: this should not happen
			wfWarn( "object_id set in EntityChange, but not entityId" );
			$idParser = new BasicEntityIdParser();
			$this->entityId = $idParser->parse( $this->getObjectId() );
		}

		return $this->entityId;
	}

	/**
	 * Set the Change's entity id (as returned by getEntityId) and the object_id field
	 * @param EntityId $entityId
	 */
	public function setEntityId( EntityId $entityId ) {
		$this->entityId = $entityId;
		$this->setField( 'object_id', $entityId->getSerialization() );
	}

	/**
	 * @return string
	 */
	public function getAction() {
		list( , $action ) = explode( '~', $this->getType(), 2 );

		return $action;
	}

	/**
	 * @param string $cache set to 'cache' to cache the unserialized diff.
	 *
	 * @return array false if no meta data could be found in the info array
	 */
	public function getMetadata( $cache = 'no' ) {
		$info = $this->getInfo( $cache );

		if ( array_key_exists( 'metadata', $info ) ) {
			return $info['metadata'];
		}

		return [];
	}

	/**
	 * Sets metadata fields. Unknown fields are ignored. New metadata is merged into
	 * the current metadata array.
	 *
	 * @param array $metadata
	 */
	public function setMetadata( array $metadata ) {
		$validKeys = [
			'page_id',
			'bot',
			'rev_id',
			'parent_id',
			'central_user_id',
			'user_text',
			'comment'
		];

		// strip extra fields from metadata
		$metadata = array_intersect_key( $metadata, array_flip( $validKeys ) );

		// merge new metadata into current metadata
		$metadata = array_merge( $this->getMetadata(), $metadata );

		// make sure the comment field is set
		if ( !isset( $metadata['comment'] ) ) {
			$metadata['comment'] = $this->getComment();
		}

		$info = $this->getInfo();
		$info['metadata'] = $metadata;
		$this->setField( 'info', $info );
	}

	/**
	 * @return string
	 */
	public function getComment() {
		$metadata = $this->getMetadata();

		// TODO: get rid of this awkward fallback and messages. Comments and messages
		// should come from the revision, not be invented here.
		if ( !isset( $metadata['comment'] ) ) {
			// Messages: wikibase-comment-add, wikibase-comment-remove, wikibase-comment-linked,
			// wikibase-comment-unlink, wikibase-comment-restore, wikibase-comment-update
			$metadata['comment'] = 'wikibase-comment-' . $this->getAction();
		}

		return $metadata['comment'];
	}

	/**
	 * @param RecentChange $rc
	 * @param int $centralUserId Central user ID, or 0 if unknown or not applicable
	 *   (see docs/change-propagation.wiki)
	 *
	 * @todo rename to setRecentChangeInfo
	 */
	public function setMetadataFromRC( RecentChange $rc, $centralUserId ) {
		$this->setFields( [
			'revision_id' => $rc->getAttribute( 'rc_this_oldid' ),
			'time' => $rc->getAttribute( 'rc_timestamp' ),
		] );

		$this->setMetadata( [
			'bot' => $rc->getAttribute( 'rc_bot' ),
			'page_id' => $rc->getAttribute( 'rc_cur_id' ),
			'rev_id' => $rc->getAttribute( 'rc_this_oldid' ),
			'parent_id' => $rc->getAttribute( 'rc_last_oldid' ),
			'comment' => $rc->getAttribute( 'rc_comment' ),
		] );

		$this->addUserMetadata(
			$rc->getAttribute( 'rc_user' ),
			$rc->getAttribute( 'rc_user_text' ),
			$centralUserId
		);
	}

	/**
	 * Add fields and metadata related to the user.
	 *
	 * This does not touch other fields or metadata.
	 *
	 * @param int $repoUserId User ID on wiki where change was made, or 0 for anon
	 * @param string $repoUserText User text on wiki where change was made, for either
	 *   logged in user or anon
	 * @param int $centralUserId Central user ID, or 0 if unknown or not applicable
	 *   (see docs/change-propagation.wiki)
	 */
	private function addUserMetadata( $repoUserId, $repoUserText, $centralUserId ) {
		$this->setFields( [
			'user_id' => $repoUserId,
		] );

		$metadata = [
			'user_text' => $repoUserText,
			'central_user_id' => $centralUserId,
		];

		$this->setMetadata( $metadata );
	}

	/**
	 * @todo rename to setUserInfo
	 *
	 * @param User $user User that made change
	 * @param int $centralUserId Central user ID, or 0 if unknown or not applicable
	 *   (see docs/change-propagation.wiki)
	 */
	public function setMetadataFromUser( User $user, $centralUserId ) {
		$this->addUserMetadata(
			$user->getId(),
			$user->getName(),
			$centralUserId
		);

		// TODO: init page_id etc in getMetadata, not here!
		$metadata = array_merge( [
				'page_id' => 0,
				'rev_id' => 0,
				'parent_id' => 0,
			],
			$this->getMetadata()
		);

		$this->setMetadata( $metadata );
	}

	/**
	 * @param Revision $revision Revision to populate EntityChange from
	 * @param int $centralUserId Central user ID, or 0 if unknown or not applicable
	 *   (see docs/change-propagation.wiki)
	 */
	public function setRevisionInfo( Revision $revision, $centralUserId ) {
		$this->setFields( [
			'revision_id' => $revision->getId(),
			'time' => $revision->getTimestamp(),
		] );

		if ( !$this->hasField( 'object_id' ) ) {
			/** @var EntityContent $content */
			$content = $revision->getContent(); // potentially expensive!
			$entityId = $content->getEntityId();

			$this->setFields( [
				'object_id' => $entityId->getSerialization(),
			] );
		}

		$this->setMetadata( [
			'page_id' => $revision->getPage(),
			'parent_id' => $revision->getParentId(),
			'comment' => $revision->getComment(),
			'rev_id' => $revision->getId(),
		] );

		$this->addUserMetadata(
			$revision->getUser(),
			$revision->getUserText(),
			$centralUserId
		);
	}

	/**
	 * @param string $timestamp Timestamp in TS_MW format
	 */
	public function setTimestamp( $timestamp ) {
		$this->setField( 'time', $timestamp );
	}

	/**
	 * @see ChangeRow::getSerializedInfo
	 *
	 * @param string[] $skipKeys
	 *
	 * @return string JSON
	 */
	public function getSerializedInfo( $skipKeys = [] ) {
		$info = $this->getInfo();

		$info = array_diff_key( $info, array_flip( $skipKeys ) );

		if ( isset( $info['compactDiff'] ) ) {
			$diff = $info['compactDiff'];

			if ( $diff instanceof EntityDiffChangedAspects ) {
				$info['compactDiff'] = $diff->serialize();
			}
		}

		// Make sure we never serialize objects.
		// This is a lot of overhead, so we only do it during testing.
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			array_walk_recursive(
				$info,
				function ( $v ) {
					if ( is_object( $v ) ) {
						throw new MWException( "Refusing to serialize PHP object of type " .
							get_class( $v ) );
					}
				}
			);
		}

		//XXX: we could JSON_UNESCAPED_UNICODE here, perhaps.
		return json_encode( $info );
	}

	/**
	 * @see ChangeRow::unserializeInfo
	 *
	 * Overwritten to use the array representation of the diff.
	 *
	 * @param string $serialization
	 * @return array the info array
	 */
	protected function unserializeInfo( $serialization ) {
		static $factory = null;

		$info = parent::unserializeInfo( $serialization );

		if ( isset( $info['compactDiff'] ) && is_string( $info['compactDiff'] ) ) {
			$compactDiff = EntityDiffChangedAspects::newEmpty();
			$compactDiff->unserialize( $info['compactDiff'] );
			$info['compactDiff'] = $compactDiff;
		}

		return $info;
	}

}
