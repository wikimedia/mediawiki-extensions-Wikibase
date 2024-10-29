<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use LogicException;
use MediaWiki\Api\IApiMessage;
use MediaWiki\Context\IContextSource;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Status\Status;
use MediaWiki\User\User;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\EditPrevented;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\RateLimitReached;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\ResourceTooLargeException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\TempAccountCreationLimitReached;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\Exceptions\EntityUpdateFailed;
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
	private EntityStore $entityStore;
	private GuidGenerator $statementIdGenerator;
	private SettingsArray $repoSettings;

	public function __construct(
		IContextSource $context,
		MediaWikiEditEntityFactory $editEntityFactory,
		LoggerInterface $logger,
		EditSummaryFormatter $summaryFormatter,
		PermissionManager $permissionManager,
		EntityStore $entityStore,
		GuidGenerator $statementIdGenerator,
		SettingsArray $repoSettings
	) {
		$this->context = $context;
		$this->editEntityFactory = $editEntityFactory;
		$this->logger = $logger;
		$this->summaryFormatter = $summaryFormatter;
		$this->permissionManager = $permissionManager;
		$this->entityStore = $entityStore;
		$this->statementIdGenerator = $statementIdGenerator;
		$this->repoSettings = $repoSettings;
	}

	/**
	 * @throws EntityUpdateFailed
	 * @throws ResourceTooLargeException
	 * @throws EditPrevented
	 * @throws RateLimitReached
	 * @throws TempAccountCreationLimitReached
	 */
	public function create( EntityDocument $entity, EditMetadata $editMetadata ): EntityRevision {
		return $this->createOrUpdate( $entity, $editMetadata, EDIT_NEW );
	}

	/**
	 * @throws EntityUpdateFailed
	 * @throws ResourceTooLargeException
	 * @throws EditPrevented
	 * @throws RateLimitReached
	 * @throws TempAccountCreationLimitReached
	 */
	public function update( EntityDocument $entity, EditMetadata $editMetadata ): EntityRevision {
		return $this->createOrUpdate( $entity, $editMetadata, EDIT_UPDATE );
	}

	/**
	 * @throws EntityUpdateFailed
	 * @throws ResourceTooLargeException
	 * @throws EditPrevented
	 * @throws RateLimitReached
	 * @throws TempAccountCreationLimitReached
	 */
	private function createOrUpdate(
		EntityDocument $entity,
		EditMetadata $editMetadata,
		int $newOrUpdateFlag
	): EntityRevision {
		$this->checkBotRightIfProvided( $this->context->getUser(), $editMetadata->isBot() );
		$editEntity = $this->editEntityFactory->newEditEntity( $this->context, $entity->getId() );

		if ( $newOrUpdateFlag === EDIT_NEW ) {
			try {
				$this->entityStore->assignFreshId( $entity );
			} catch ( StorageException $e ) {
				if ( $e->getStatus()->hasMessage( 'actionthrottledtext' ) ) {
					throw new RateLimitReached();
				}
				throw $e; // It wasn't a rate limit error, so we rethrow as an unexpected exception.
			}
		}
		if ( $entity->getId() === null ) {
			throw new LogicException( 'The entity to be saved should have an ID at this point' );
		}
		if ( $entity instanceof StatementListProvidingEntity ) {
			$this->generateStatementIds( $entity );
		}

		$status = $editEntity->attemptSave(
			$entity,
			$this->summaryFormatter->format( $editMetadata->getSummary() ),
			$newOrUpdateFlag | ( $editMetadata->isBot() ? EDIT_FORCE_BOT : 0 ),
			false,
			false,
			$editMetadata->getTags()
		);

		if ( !$status->isOK() ) {
			if ( $status->hasMessage( 'wikibase-error-entity-too-big' ) ) {
				throw new ResourceTooLargeException( $this->repoSettings->getSetting( 'maxSerializedEntitySize' ) );
			} elseif ( $status->hasMessage( 'acct_creation_throttle_hit' ) ) {
				throw new TempAccountCreationLimitReached();
			} elseif ( $status->hasMessage( 'actionthrottledtext' ) ) {
				throw new RateLimitReached();
			}

			$this->throwIfPreventedEdit( $status );

			throw new EntityUpdateFailed( (string)$status );
		} elseif ( !$status->isGood() ) {
			$this->logger->warning( (string)$status );
		}

		return $status->getRevision();
	}

	/**
	 * @throws EditPrevented
	 */
	private function throwIfPreventedEdit( Status $status ): void {
		foreach ( $status->getMessages() as $message ) {
			if ( $message instanceof IApiMessage ) {
				throw new EditPrevented( $message->getApiCode(), $message->getApiData() );
			}
		}
	}

	private function checkBotRightIfProvided( User $user, bool $isBot ): void {
		// This is only a low-level safeguard and should be checked and handled properly before using this service.
		if ( $isBot && !$this->permissionManager->userHasRight( $user, 'bot' ) ) {
			throw new RuntimeException( 'Attempted bot edit with insufficient rights' );
		}
	}

	private function generateStatementIds( StatementListProvidingEntity $entity ): void {
		foreach ( $entity->getStatements() as $statement ) {
			if ( $statement->getGuid() === null ) {
				$statement->setGuid( $this->statementIdGenerator->newGuid( $entity->getId() ) );
			}
		}
	}

}
