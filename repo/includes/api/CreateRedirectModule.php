<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use ApiResult;
use UsageException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Repo\Interactors\RedirectCreationException;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for creating entity redirects.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CreateRedirectModule extends ApiBase {

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
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$this->setServices(
			WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
			WikibaseRepo::getDefaultInstance()->getApiHelperFactory()->getErrorReporter( $this ),
			new RedirectCreationInteractor(
				WikibaseRepo::getDefaultInstance()->getEntityRevisionLookup( 'uncached' ),
				WikibaseRepo::getDefaultInstance()->getEntityStore(),
				WikibaseRepo::getDefaultInstance()->getEntityPermissionChecker(),
				WikibaseRepo::getDefaultInstance()->getSummaryFormatter(),
				$this->getUser()
			)
		);
	}

	public function setServices(
		EntityIdParser $idParser,
		ApiErrorReporter $errorReporter,
		RedirectCreationInteractor $interactor
	) {
		$this->idParser = $idParser;
		$this->errorReporter = $errorReporter;
		$this->interactor = $interactor;
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

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

		wfProfileOut( __METHOD__ );
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
	 * @throws UsageException always
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
	 * @return string The empty string to indicate we need a token, but no salt.
	 */
	public function getTokenSalt() {
		return '';
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
		return array(
			'from' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'to' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'token' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => 'true',
			),
			'bot' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false,
			)
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 *
	 * @return array
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbcreateredirect&from=Q11&to=Q12'
				=> 'apihelp-wbcreateredirect-example-1',
		);
	}

}
