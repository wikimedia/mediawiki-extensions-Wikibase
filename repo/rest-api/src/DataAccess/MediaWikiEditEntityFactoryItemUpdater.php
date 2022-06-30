<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use IContextSource;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class MediaWikiEditEntityFactoryItemUpdater implements ItemUpdater {

	public const DEFAULT_COMMENT = 'Wikibase REST API edit';

	private $context;
	private $editEntityFactory;

	public function __construct( IContextSource $context, MediawikiEditEntityFactory $editEntityFactory ) {
		$this->context = $context;
		$this->editEntityFactory = $editEntityFactory;
	}

	public function update( Item $item, EditMetadata $editMetadata ): ?ItemRevision {
		$editEntity = $this->editEntityFactory->newEditEntity( $this->context, $item->getId() );

		$status = $editEntity->attemptSave(
			$item,
			$editMetadata->getComment() ?? self::DEFAULT_COMMENT,
			EDIT_UPDATE | ( $editMetadata->isBot() ? EDIT_FORCE_BOT : 0 ),
			false,
			false,
			$editMetadata->getTags()
		);

		if ( $status->isGood() ) {
			/** @var EntityRevision $entityRevision */
			$entityRevision = $status->getValue()['revision'];
			/** @var Item $savedItem */
			$savedItem = $entityRevision->getEntity();
			'@phan-var Item $savedItem';

			return new ItemRevision( $savedItem, $entityRevision->getTimestamp(), $entityRevision->getRevisionId() );
		}

		return null;
	}

}
