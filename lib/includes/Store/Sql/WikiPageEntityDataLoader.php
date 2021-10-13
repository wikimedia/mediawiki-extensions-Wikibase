<?php

namespace Wikibase\Lib\Store\Sql;

use MediaWiki\Revision\RevisionAccessException;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\BlobAccessException;
use MediaWiki\Storage\BlobStore;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\StorageException;

/**
 * @license GPL-2.0-or-later
 */
class WikiPageEntityDataLoader {

	/**
	 * @var EntityContentDataCodec
	 */
	private $contentCodec;

	/**
	 * @var BlobStore
	 *
	 * @todo remove this once we no longer need to be compatible to the pre-MCR database schema
	 */
	private $blobStore;

	/** @var string|false */
	private $wikiId;

	public function __construct(
		EntityContentDataCodec $contentCodec,
		BlobStore $blobStore,
		$wikiId = false
	) {
		$this->contentCodec = $contentCodec;
		$this->blobStore = $blobStore;
		$this->wikiId = $wikiId;
	}

	/**
	 * @param RevisionRecord $revision
	 * @param string $slotRole
	 * @param int $revStoreFlags See IDBAccessObject.
	 *
	 * @throws StorageException
	 * @return array list( EntityRevision|null $entityRevision, EntityRedirect|null $entityRedirect )
	 * with either $entityRevision or $entityRedirect or both being null (but not both being non-null).
	 */
	public function loadEntityDataFromWikiPageRevision( RevisionRecord $revision, string $slotRole, int $revStoreFlags ) {
		// NOTE: Support for cross-wiki content access in RevisionStore is incomplete when,
		// reading from the pre-MCR database schema, see T201194.
		// For that reason, we have to load and decode the content blob directly,
		// instead of using RevisionRecord::getContent() or SlotRecord::getContent().
		// TODO Once we can rely on the new MCR enabled DB schema, use getContent() directly!

		if ( !$revision->hasSlot( $slotRole ) ) {
			return [ null, null ];
		}

		try {
			$slot = $revision->getSlot( $slotRole );
		} catch ( RevisionAccessException $e ) {
			throw new StorageException( 'Failed to load slot', 0, $e );
		}

		// WARNING: This will make it look like suppressed revisions don't exist at all.
		// Wikibase should handle old revisions with suppressed content gracefully.
		// @see https://phabricator.wikimedia.org/T198467
		if ( !$revision->audienceCan( RevisionRecord::DELETED_TEXT, RevisionRecord::FOR_PUBLIC ) ) {
			return [ null, null ];
		}

		try {
			$blob = $this->blobStore->getBlob( $slot->getAddress(), $revStoreFlags );
		} catch ( BlobAccessException $e ) {
			throw new StorageException( 'Failed to load blob', 0, $e );
		}

		$entity = $this->contentCodec->decodeEntity( $blob, $slot->getFormat() );

		if ( $entity ) {
			$entityRevision = new EntityRevision(
				$entity,
				$revision->getId( $this->wikiId ),
				$revision->getTimestamp()
			);

			return [ $entityRevision, null ];
		} else {
			$redirect = $this->contentCodec->decodeRedirect( $blob, $slot->getFormat() );

			if ( !$redirect ) {
				throw new StorageException(
					'The serialized data of revision ' . $revision->getId( $this->wikiId )
					. ' contains neither an Entity nor an EntityRedirect!'
				);
			}

			return [ null, $redirect ];
		}
	}

}
