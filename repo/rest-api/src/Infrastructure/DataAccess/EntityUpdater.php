<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use IApiMessage;
use LogicException;
use MediaWiki\Context\IContextSource;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Status\Status;
use MediaWiki\User\User;
use MessageSpecifier;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\AbuseFilterException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\ResourceTooLargeException;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\Exceptions\EntityUpdateFailed;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\Exceptions\EntityUpdatePrevented;
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

	public function __construct(
		IContextSource $context,
		MediaWikiEditEntityFactory $editEntityFactory,
		LoggerInterface $logger,
		EditSummaryFormatter $summaryFormatter,
		PermissionManager $permissionManager,
		EntityStore $entityStore,
		GuidGenerator $statementIdGenerator
	) {
		$this->context = $context;
		$this->editEntityFactory = $editEntityFactory;
		$this->logger = $logger;
		$this->summaryFormatter = $summaryFormatter;
		$this->permissionManager = $permissionManager;
		$this->entityStore = $entityStore;
		$this->statementIdGenerator = $statementIdGenerator;
	}

	/**
	 * @throws EntityUpdateFailed
	 */
	public function create( EntityDocument $entity, EditMetadata $editMetadata ): EntityRevision {
		return $this->createOrUpdate( $entity, $editMetadata, EDIT_NEW );
	}

	/**
	 * @throws EntityUpdateFailed
	 */
	public function update( EntityDocument $entity, EditMetadata $editMetadata ): EntityRevision {
		return $this->createOrUpdate( $entity, $editMetadata, EDIT_UPDATE );
	}

	/**
	 * @throws EntityUpdateFailed
	 * @throws ResourceTooLargeException
	 * @throws AbuseFilterException
	 */
	private function createOrUpdate(
		EntityDocument $entity,
		EditMetadata $editMetadata,
		int $newOrUpdateFlag
	): EntityRevision {
		$this->checkBotRightIfProvided( $this->context->getUser(), $editMetadata->isBot() );
		$editEntity = $this->editEntityFactory->newEditEntity( $this->context, $entity->getId() );

		if ( $newOrUpdateFlag === EDIT_NEW ) {
			$this->entityStore->assignFreshId( $entity );
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
			$entityTooBigError = $this->findErrorInStatus( $status, 'wikibase-error-entity-too-big' );
			if ( $entityTooBigError ) {
				$maxSizeInKiloBytes = $entityTooBigError->getParams()[0]['size'] / 1024;
				throw new ResourceTooLargeException( $maxSizeInKiloBytes );
			}

			$abuseFilterError = $this->findAbuseFilterError( $status->getMessages() );
			if ( $abuseFilterError ) {
				throw new AbuseFilterException(
					$abuseFilterError->getApiData()['abusefilter']['id'],
					$abuseFilterError->getApiData()['abusefilter']['description']
				);
			}

			if ( $this->isPreventedEdit( $status ) ) {
				throw new EntityUpdatePrevented( (string)$status );
			}

			throw new EntityUpdateFailed( (string)$status );
		} elseif ( !$status->isGood() ) {
			$this->logger->warning( (string)$status );
		}

		return $status->getRevision();
	}

	private function isPreventedEdit( Status $status ): bool {
		return $this->findErrorInStatus( $status, 'actionthrottledtext' )
			|| $this->findErrorInStatus( $status, 'spam-blacklisted' );
	}

	private function findErrorInStatus( Status $status, string $errorCode ): ?MessageSpecifier {
		foreach ( $status->getMessages() as $message ) {
			// prefix comparison to cover different kinds of spam-blacklisted or abusefilter errors
			if ( strpos( $message->getKey(), $errorCode ) === 0 ) {
				return $message;
			}
		}

		return null;
	}

	private function findAbuseFilterError( array $messages ): ?IApiMessage {
		foreach ( $messages as $message ) {
			if ( $message instanceof IApiMessage &&
				in_array( $message->getApiCode(), [ 'abusefilter-warning', 'abusefilter-disallowed' ] ) ) {
				return $message;
			}
		}

		return null;
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
