<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use IContextSource;
use MediaWiki\Permissions\PermissionManager;
use Psr\Log\LoggerInterface;
use RuntimeException;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\EntityUpdateFailed;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\EntityUpdatePrevented;
use Wikibase\Repo\RestApi\Infrastructure\EditSummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class EntityUpdater {

	private IContextSource $context;
	private MediaWikiEditEntityFactory $editEntityFactory;
	private LoggerInterface $logger;
	private EditSummaryFormatter $summaryFormatter;
	private PermissionManager $permissionManager;

	public function __construct(
		IContextSource $context,
		MediaWikiEditEntityFactory $editEntityFactory,
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

	public function update( EntityDocument $entity, EditMetadata $editMetadata ): EntityRevision {
		$this->checkBotRightIfProvided( $this->context->getUser(), $editMetadata->isBot() );

		$editEntity = $this->editEntityFactory->newEditEntity( $this->context, $entity->getId() );

		$status = $editEntity->attemptSave(
			$entity,
			$this->summaryFormatter->format( $editMetadata->getSummary() ),
			EDIT_UPDATE | ( $editMetadata->isBot() ? EDIT_FORCE_BOT : 0 ),
			false,
			false,
			$editMetadata->getTags()
		);

		if ( !$status->isOK() ) {
			if ( $this->isPreventedEdit( $status ) ) {
				throw new EntityUpdatePrevented( (string)$status );
			}

			throw new EntityUpdateFailed( (string)$status );
		} elseif ( !$status->isGood() ) {
			$this->logger->warning( (string)$status );
		}

		return $status->getValue()['revision'];
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

}
