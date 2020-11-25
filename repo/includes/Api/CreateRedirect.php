<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use ApiResult;
use ApiUsageException;
use MediaWiki\Permissions\PermissionManager;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\Interactors\RedirectCreationException;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for creating entity redirects.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class CreateRedirect extends ApiBase {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var ItemRedirectCreationInteractor
	 */
	private $interactor;

	/**
	 * @var PermissionManager
	 */
	private $permissionManager;

	/**
	 * @see ApiBase::__construct
	 *
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param EntityIdParser $idParser
	 * @param ApiErrorReporter $errorReporter
	 * @param ItemRedirectCreationInteractor $interactor
	 * @param PermissionManager $permissionManager
	 */
	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		EntityIdParser $idParser,
		ApiErrorReporter $errorReporter,
		ItemRedirectCreationInteractor $interactor,
		PermissionManager $permissionManager
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->idParser = $idParser;
		$this->errorReporter = $errorReporter;
		$this->interactor = $interactor;
		$this->permissionManager = $permissionManager;
	}

	public static function factory(
		ApiMain $apiMain,
		string $moduleName,
		PermissionManager $permissionManager,
		EntityIdParser $entityIdParser
	): self {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $apiMain->getContext() );
		return new self(
			$apiMain,
			$moduleName,
			$entityIdParser,
			$apiHelperFactory->getErrorReporter( $apiMain ),
			$wikibaseRepo->newItemRedirectCreationInteractor( $apiMain->getUser(), $apiMain->getContext() ),
			$permissionManager
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();
		$bot = $params['bot'] &&
			$this->permissionManager->userHasRight( $this->getUser(), 'bot' );

		try {
			$fromId = $this->idParser->parse( $params['from'] );
			$toId = $this->idParser->parse( $params['to'] );

			$this->createRedirect( $fromId, $toId, $bot, $this->getResult() );
		} catch ( EntityIdParsingException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-entity-id' );
		} catch ( RedirectCreationException $ex ) {
			$this->handleRedirectCreationException( $ex );
		}
	}

	/**
	 * @param EntityId $fromId
	 * @param EntityId $toId
	 * @param bool $bot Whether the edit should be marked as bot
	 * @param ApiResult $result The result object to report the result to.
	 *
	 * @throws RedirectCreationException
	 */
	private function createRedirect( EntityId $fromId, EntityId $toId, bool $bot, ApiResult $result ): void {
		$this->interactor->createRedirect( $fromId, $toId, $bot );

		$result->addValue( null, 'success', 1 );
		$result->addValue( null, 'redirect', $toId->getSerialization() );
	}

	/**
	 * @param RedirectCreationException $ex
	 *
	 * @throws ApiUsageException always
	 */
	private function handleRedirectCreationException( RedirectCreationException $ex ): void {
		$cause = $ex->getPrevious();

		if ( $cause ) {
			$this->errorReporter->dieException( $cause, $ex->getErrorCode() );
		} else {
			$this->errorReporter->dieError( $ex->getMessage(), $ex->getErrorCode() );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function isWriteMode(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken(): string {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	public function mustBePosted(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return [
			'from' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			],
			'to' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			],
			'token' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			],
			'bot' => [
				self::PARAM_TYPE => 'boolean',
				self::PARAM_DFLT => false,
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		return [
			'action=wbcreateredirect&from=Q11&to=Q12'
				=> 'apihelp-wbcreateredirect-example-1',
		];
	}

}
