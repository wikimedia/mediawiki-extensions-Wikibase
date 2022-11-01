<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use IContextSource;
use MediaWiki\Permissions\PermissionManager;
use Psr\Log\LoggerInterface;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdateFailed;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Infrastructure\EditSummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class MediaWikiEditEntityFactoryItemUpdater implements ItemUpdater {

	private IContextSource $context;
	private MediawikiEditEntityFactory $editEntityFactory;
	private LoggerInterface $logger;
	private EditSummaryFormatter $summaryFormatter;
	private PermissionManager $permissionManager;

	public function __construct(
		IContextSource $context,
		MediawikiEditEntityFactory $editEntityFactory,
		LoggerInterface $logger,
		EditSummaryFormatter $summaryFormatter,
		PermissionManager $permissionManager
	) {
		$this->context = $context;
		$this->editEntityFactory = $editEntityFactory;
		$this->logger = $logger;
		$this->summaryFormatter = $summaryFormatter;
		$this->permissionManager = $permissionManager;
	}

	public function update( Item $item, EditMetadata $editMetadata ): ItemRevision {
		$this->checkBotRightIfProvided( $this->context->getUser(), $editMetadata->isBot() );

		$editEntity = $this->editEntityFactory->newEditEntity( $this->context, $item->getId() );

		$status = $editEntity->attemptSave(
			$item,
			$this->summaryFormatter->format( $editMetadata->getSummary() ),
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

	private function checkBotRightIfProvided( User $user, bool $isBot ): void {
		// This is only a low-level safeguard and should be checked and handled properly before using this service.
		if ( $isBot && !$this->permissionManager->userHasRight( $user, 'bot' ) ) {
			throw new \RuntimeException( 'Attempted bot edit with insufficient rights' );
		}
	}

}
