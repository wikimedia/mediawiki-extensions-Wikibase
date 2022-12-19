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
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\Interactors\RedirectCreationException;
use Wikimedia\ParamValidator\ParamValidator;

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

	/** @var string[] */
	private $sandboxEntityIds;

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
		PermissionManager $permissionManager,
		array $sandboxEntityIds
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->idParser = $idParser;
		$this->errorReporter = $errorReporter;
		$this->interactor = $interactor;
		$this->permissionManager = $permissionManager;
		$this->sandboxEntityIds = $sandboxEntityIds;
	}

	public static function factory(
		ApiMain $apiMain,
		string $moduleName,
		PermissionManager $permissionManager,
		ApiHelperFactory $apiHelperFactory,
		EntityIdParser $entityIdParser,
		ItemRedirectCreationInteractor $interactor,
		SettingsArray $settings
	): self {
		return new self(
			$apiMain,
			$moduleName,
			$entityIdParser,
			$apiHelperFactory->getErrorReporter( $apiMain ),
			$interactor,
			$permissionManager,
			$settings->getSetting( 'sandboxEntityIds' )
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
		$this->interactor->createRedirect( $fromId, $toId, $bot, [], $this->getContext() ); // TODO pass through $tags (T229918)

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
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'to' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'token' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'bot' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_DEFAULT => false,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		$from = $this->sandboxEntityIds['mainItem'];
		$to = $this->sandboxEntityIds['auxItem'];

		return [
			'action=wbcreateredirect&from=' . $from . '&to=' . $to
				=> [ 'apihelp-wbcreateredirect-example-1', $from, $to ],
		];
	}

}
