<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use ApiResult;
use ApiUsageException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Repo\Interactors\RedirectCreationException;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;

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
	 * @var RedirectCreationInteractor
	 */
	private $interactor;

	/**
	 * @see ApiBase::__construct
	 *
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param EntityIdParser $idParser
	 * @param ApiErrorReporter $errorReporter
	 * @param RedirectCreationInteractor $interactor
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		EntityIdParser $idParser,
		ApiErrorReporter $errorReporter,
		RedirectCreationInteractor $interactor
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->idParser = $idParser;
		$this->errorReporter = $errorReporter;
		$this->interactor = $interactor;
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$bot = $this->getUser()->isAllowed( 'bot' ) && $params['bot'];

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
	private function createRedirect( EntityId $fromId, EntityId $toId, $bot, ApiResult $result ) {
		$this->interactor->createRedirect( $fromId, $toId, $bot );

		$result->addValue( null, 'success', 1 );
		$result->addValue( null, 'redirect', $toId->getSerialization() );
	}

	/**
	 * @param RedirectCreationException $ex
	 *
	 * @throws ApiUsageException always
	 */
	private function handleRedirectCreationException( RedirectCreationException $ex ) {
		$cause = $ex->getPrevious();

		if ( $cause ) {
			$this->errorReporter->dieException( $cause, $ex->getErrorCode() );
		} else {
			$this->errorReporter->dieError( $ex->getMessage(), $ex->getErrorCode() );
		}
	}

	/**
	 * @see ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see ApiBase::needsToken()
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @see ApiBase::mustBePosted()
	 */
	public function mustBePosted() {
		return true;
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
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
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return [
			'action=wbcreateredirect&from=Q11&to=Q12'
				=> 'apihelp-wbcreateredirect-example-1',
		];
	}

}
