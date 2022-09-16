<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use IContextSource;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdateFailed;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class MediaWikiEditEntityFactoryItemUpdater implements ItemUpdater {

	private $context;
	private $editEntityFactory;
	private $logger;

	public function __construct( IContextSource $context, MediawikiEditEntityFactory $editEntityFactory, LoggerInterface $logger ) {
		$this->context = $context;
		$this->editEntityFactory = $editEntityFactory;
		$this->logger = $logger;
	}

	public function update( Item $item, EditMetadata $editMetadata ): ItemRevision {
		$editEntity = $this->editEntityFactory->newEditEntity( $this->context, $item->getId() );

		$status = $editEntity->attemptSave(
			$item,
			$editMetadata->getSummary()->getUserComment() ?? '',
			EDIT_UPDATE | ( $editMetadata->isBot() ? EDIT_FORCE_BOT : 0 ),
			false,
			false,
			$editMetadata->getTags()
		);

		if ( !$status->isOK() ) {
			throw new ItemUpdateFailed( (string)$status );
		} elseif ( !$status->isGood() ) {
			$this->logger->warning( (string)$status );
		}

		/** @var EntityRevision $entityRevision */
		$entityRevision = $status->getValue()['revision'];
		/** @var Item $savedItem */
		$savedItem = $entityRevision->getEntity();
		'@phan-var Item $savedItem';

		return new ItemRevision( $savedItem, $entityRevision->getTimestamp(), $entityRevision->getRevisionId() );
	}

}
