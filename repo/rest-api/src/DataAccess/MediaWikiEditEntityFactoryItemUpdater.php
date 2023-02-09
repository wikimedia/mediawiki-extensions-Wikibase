<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use IContextSource;
use MediaWiki\Permissions\PermissionManager;
use Psr\Log\LoggerInterface;
use RuntimeException;
use User;
use Wikibase\DataModel\Entity\Item as DataModelItem;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdateFailed;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdatePrevented;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
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
	private StatementReadModelConverter $statementReadModelConverter;

	public function __construct(
		IContextSource $context,
		MediawikiEditEntityFactory $editEntityFactory,
		LoggerInterface $logger,
		EditSummaryFormatter $summaryFormatter,
		PermissionManager $permissionManager,
		StatementReadModelConverter $statementReadModelConverter
	) {
		$this->context = $context;
		$this->editEntityFactory = $editEntityFactory;
		$this->logger = $logger;
		$this->summaryFormatter = $summaryFormatter;
		$this->permissionManager = $permissionManager;
		$this->statementReadModelConverter = $statementReadModelConverter;
	}

	public function update( DataModelItem $item, EditMetadata $editMetadata ): ItemRevision {
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
			if ( $this->isPreventedEdit( $status ) ) {
				throw new ItemUpdatePrevented( (string)$status );
			}

			throw new ItemUpdateFailed( (string)$status );
		} elseif ( !$status->isGood() ) {
			$this->logger->warning( (string)$status );
		}

		/** @var EntityRevision $entityRevision */
		$entityRevision = $status->getValue()['revision'];
		/** @var DataModelItem $savedItem */
		$savedItem = $entityRevision->getEntity();
		'@phan-var DataModelItem $savedItem';

		return new ItemRevision(
			$this->convertDataModelItemToReadModel( $savedItem ),
			$entityRevision->getTimestamp(),
			$entityRevision->getRevisionId()
		);
	}

	private function isPreventedEdit( \Status $status ): bool {
		$errorMessage = $status->getErrors()[0]['message'];
		$errorCode = is_string( $errorMessage ) ? $errorMessage : $errorMessage->getKey();

		return $errorCode === 'actionthrottledtext'
			|| strpos( $errorCode, 'spam-blacklisted' ) === 0
			|| strpos( $errorCode, 'abusefilter' ) === 0;
	}

	private function checkBotRightIfProvided( User $user, bool $isBot ): void {
		// This is only a low-level safeguard and should be checked and handled properly before using this service.
		if ( $isBot && !$this->permissionManager->userHasRight( $user, 'bot' ) ) {
			throw new RuntimeException( 'Attempted bot edit with insufficient rights' );
		}
	}

	private function convertDataModelItemToReadModel( DataModelItem $item ): Item {
		return new Item( new StatementList( ...array_map(
			[ $this->statementReadModelConverter, 'convert' ],
			iterator_to_array( $item->getStatements() )
		) ) );
	}

}
