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

		try {
			$fromId = $this->idParser->parse( $params['from'] );
			$toId = $this->idParser->parse( $params['to'] );

			$this->createRedirect( $fromId, $toId, $this->getResult() );
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
	 * @param ApiResult $result The result object to report the result to.
	 *
	 * @throws RedirectCreationException
	 */
	private function createRedirect( EntityId $fromId, EntityId $toId, ApiResult $result ) {
		$this->interactor->createRedirect( $fromId, $toId );

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
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		$errors = array();

		foreach ( $this->interactor->getErrorCodeInfo() as $code => $info ) {
			$errors[$code] = $info;
		}

		return array_merge( parent::getPossibleErrors(), $errors );
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
	 * Returns an array of allowed parameters (parameter name) => (default
	 * value) or (parameter name) => (array with PARAM_* constants as keys)
	 * Don't call this function directly: use getFinalParams() to allow
	 * hooks to modify parameters as needed.
	 * @return array|bool
	 */
	public function getAllowedParams() {
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
	 * Get final parameter descriptions, after hooks have had a chance to tweak it as
	 * needed.
	 *
	 * @return array|bool False on no parameter descriptions
	 */
	public function getParamDescription() {
		return array(
			'from' => array( 'Entity ID to make a redirect' ),
			'to' => array( 'Entity ID to point the redirect to' ),
			'token' => 'A "edittoken" token previously obtained through the token module',
			'bot' => array( 'Mark this edit as bot',
				'This URL flag will only be respected if the user belongs to the group "bot".'
			),
		);
	}

	/**
	 * Returns the description string for this module
	 * @return mixed string or array of strings
	 */
	public function getDescription() {
		return array(
			'API module for creating Entity redirects.'
		);
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbcreateredirect&from=Q11&to=Q12'
				=> 'Turn Q11 into a redirect to Q12',
		);
	}

}
