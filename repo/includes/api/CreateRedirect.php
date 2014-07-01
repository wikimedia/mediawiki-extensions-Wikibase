<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use LogicException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StorageException;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * API module for creating entity redirects.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CreateRedirect extends ApiBase { //FIXME TESTME

	/**
	 * @var array
	 */
	private $params;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$errorReporter = new ApiErrorReporter(
			$this,
			WikibaseRepo::getDefaultInstance()->getExceptionLocalizer(),
			$this->getLanguage()
		);
		
		$this->setServices(
			WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
			WikibaseRepo::getDefaultInstance()->getEntityRevisionLookup( 'uncached' ),
			WikibaseRepo::getDefaultInstance()->getEntityStore(),
			$errorReporter,
			WikibaseRepo::getDefaultInstance()->getSummaryFormatter()
		);
	}

	public function setServices(
		EntityIdParser $idParser,
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		ApiErrorReporter $errorReporter,
		SummaryFormatter $summaryFormatter
	) {
		$this->idParser = $idParser;
		$this->entityRevisionLookup =$entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->summaryFormatter = $summaryFormatter;
		$this->errorReporter = $errorReporter;
	}

	private function initExecute()  {
		$this->params = $this->extractRequestParams();
	}

	/**
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$this->initExecute();

		$fromId = $this->getEntityId( 'from' );
		$toId = $this->getEntityId( 'to' );

		$this->checkCompatible( $fromId, $toId );

		$this->checkExists( $toId );
		$this->checkEmpty( $fromId );

		$this->createRedirect( $fromId, $toId );

		//XXX: return a serialized version of the redirect?
		$this->getResult()->addValue( null, 'success', 1 );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @param string $param The name of the parameter to get the ID from.
	 *
	 * @throws \LogicException
	 * @return EntityId
	 */
	private function getEntityId( $param ) {
		try {
			return $this->idParser->parse( $this->params[$param] );
		} catch ( EntityIdParsingException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-entity-id' );
		}

		throw new LogicException( 'ApiErrorReporter::dieException did not throw a UsageException' );
	}

	private function checkEmpty( EntityId $id ) {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision( $id );

			if ( !$revision ) {
				$this->errorReporter->dieError(
					'Entity ' . $id->getSerialization() . ' not found',
					'no-such-entity' );
			}

			$entity = $revision->getEntity();

			if ( !$entity->isEmpty() ) {
				$this->errorReporter->dieError( 'Entity ' . $id->getSerialization() . ' is not empty', 'not-empty' );
			}
		} catch ( UnresolvedRedirectException $ex ) {
			// Nothing to do. It's ok to override a redirect with a redirect.
		} catch ( StorageException $ex ) {
			$this->errorReporter->dieException( $ex, 'cant-load-entity-content' );
		}
	}

	private function checkExists( EntityId $id ) {
		try {
			$revision = $this->entityRevisionLookup->getLatestRevisionId( $id );

			if ( !$revision ) {
				$this->errorReporter->dieError( 'Entity ' . $id->getSerialization() . ' not found', 'no-such-entity' );
			}
		} catch ( UnresolvedRedirectException $ex ) {
			$this->errorReporter->dieException( $ex, 'target-is-redirect' );
		}
	}

	private function checkCompatible( EntityId $fromId, EntityId $toId ) {
		if ( $fromId->getEntityType() !== $toId->getEntityType() ) {
			$this->errorReporter->dieError( 'Incompatible entity types', 'target-is-incompatible' );
		}
	}

	private function createRedirect( EntityId $fromId, EntityId $toId ) {
		$summary = new Summary( $this->getModuleName(), 'redirect' );
		$summary->addAutoSummaryArgs( $fromId, $toId );

		$redirect = new EntityRedirect( $fromId, $toId );

		try {
			$this->entityStore->saveRedirect(
				$redirect,
				$this->summaryFormatter->formatSummary( $summary ),
				$this->getUser(),
				EDIT_UPDATE
			);
		} catch ( StorageException $ex ) {
			$this->errorReporter->dieException( $ex, 'cant-redirect' );
		}
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'invalid-entity-id', 'info' => 'Invalid entity ID' ),
			array( 'code' => 'not-empty', 'info' => 'The entity that is to be turned into a redirect is not empty' ),
			array( 'code' => 'no-such-entity', 'info' => 'Entity not found' ),
			array( 'code' => 'target-is-redirect', 'info' => 'The redirect target is itself a redirect' ),
			array( 'code' => 'target-is-incompatible', 'info' => 'The redirect target is incompatible (e.g. a different type of entity)' ),
			array( 'code' => 'cant-redirect', 'info' => 'Can\'t create the redirect (e.g. the given type of entity does not support redirects)' ),
		) );
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
		return true;
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
			'token' => array( 'A "edittoken" token previously obtained through the token module' ),
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
