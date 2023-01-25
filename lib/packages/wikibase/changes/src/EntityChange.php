<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Changes;

use Exception;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;

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

	public const UPDATE = 'update';
	public const ADD = 'add';
	public const REMOVE = 'remove';
	public const RESTORE = 'restore';

	private ?EntityId $entityId = null;

	public function getType(): string {
		return $this->getField( ChangeRow::TYPE );
	}

	public function getEntityId(): EntityId {
		if ( !$this->entityId && $this->hasField( ChangeRow::OBJECT_ID ) ) {
			// FIXME: this should not happen
			$this->logger->warning( 'object_id set in EntityChange, but not entityId' );
			$idParser = new BasicEntityIdParser();
			$this->entityId = $idParser->parse( $this->getObjectId() );
		}

		return $this->entityId;
	}

	/**
	 * Set the Change's entity id (as returned by getEntityId) and the object_id field
	 */
	public function setEntityId( EntityId $entityId ): void {
		$this->entityId = $entityId;
		$this->setField( ChangeRow::OBJECT_ID, $entityId->getSerialization() );
	}

	public function getAction(): string {
		list( , $action ) = explode( '~', $this->getType(), 2 );

		return $action;
	}

	/**
	 * @param string $cache set to 'cache' to cache the unserialized diff.
	 *
	 * @return array
	 */
	public function getMetadata( string $cache = 'no' ): array {
		$info = $this->getInfo( $cache );

		if ( array_key_exists( ChangeRow::METADATA, $info ) ) {
			return array_merge(
				[ // these may be expected to be set by consuming code
					'page_id' => 0,
					'rev_id' => 0,
					'parent_id' => 0,
				],
				$info[ChangeRow::METADATA]
			);
		}

		return [];
	}

	/**
	 * Sets metadata fields. Unknown fields are ignored. New metadata is merged into
	 * the current metadata array.
	 */
	public function setMetadata( array $metadata ): void {
		$validKeys = [
			'page_id',
			'bot',
			'rev_id',
			'parent_id',
			'central_user_id',
			'user_text',
			'comment',
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
		$info[ChangeRow::METADATA] = $metadata;
		$this->setField( ChangeRow::INFO, $info );
	}

	public function getComment(): string {
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
	public function addUserMetadata( int $repoUserId, string $repoUserText, int $centralUserId ): void {
		$this->setFields( [
			ChangeRow::USER_ID => $repoUserId,
		] );

		$metadata = [
			'user_text' => $repoUserText,
			'central_user_id' => $centralUserId,
		];

		$this->setMetadata( $metadata );
	}

	/**
	 * @param string $timestamp Timestamp in TS_MW format
	 */
	public function setTimestamp( string $timestamp ): void {
		$this->setField( ChangeRow::TIME, $timestamp );
	}

	/**
	 * @see ChangeRow::getSerializedInfo
	 *
	 * @param string[] $skipKeys
	 *
	 * @return string JSON
	 */
	public function getSerializedInfo( $skipKeys = [] ): string {
		$info = $this->getInfo();

		$info = array_diff_key( $info, array_flip( $skipKeys ) );

		if ( isset( $info[ChangeRow::COMPACT_DIFF] ) ) {
			$diff = $info[ChangeRow::COMPACT_DIFF];

			if ( $diff instanceof EntityDiffChangedAspects ) {
				$info[ChangeRow::COMPACT_DIFF] = $diff->serialize();
			}
		}

		// Make sure we never serialize objects.
		// This is a lot of overhead, so we only do it during testing.
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			array_walk_recursive(
				$info,
				function ( $v ) {
					if ( is_object( $v ) ) {
						throw new Exception( "Refusing to serialize PHP object of type " .
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
	protected function unserializeInfo( $serialization ): array {
		static $factory = null;

		$info = parent::unserializeInfo( $serialization );

		if ( isset( $info[ChangeRow::COMPACT_DIFF] ) && is_string( $info[ChangeRow::COMPACT_DIFF] ) ) {
			$aspectsFactory = new EntityDiffChangedAspectsFactory( $this->logger );
			$compactDiff = $aspectsFactory->newEmpty();
			$compactDiff->unserialize( $info[ChangeRow::COMPACT_DIFF] );
			$info[ChangeRow::COMPACT_DIFF] = $compactDiff;
		}

		return $info;
	}

}
