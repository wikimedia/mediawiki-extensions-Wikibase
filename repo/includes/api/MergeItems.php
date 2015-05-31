<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use InvalidArgumentException;
use LogicException;
use UsageException;
use Wikibase\ChangeOp\ChangeOpsMerge;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Interactors\ItemMergeException;
use Wikibase\Repo\Interactors\RedirectCreationException;
use Wikibase\Repo\WikibaseRepo;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Daniel Kinzler
 * @author Lucie-Aimée Kaffee
 */
class MergeItems extends ApiBase {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var ItemMergeInteractor
	 */
	private $interactorMerge;

	/**
	* @var RedirectCreationInteractor
	*/
	private $interactorRedirect;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->setServices(
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getApiHelperFactory()->getErrorReporter( $this ),
			$wikibaseRepo->getApiHelperFactory()->getResultBuilder( $this ),
			new ItemMergeInteractor(
				$wikibaseRepo->getChangeOpFactoryProvider()->getMergeChangeOpFactory(),
				$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
				$wikibaseRepo->getEntityStore(),
				$wikibaseRepo->getEntityPermissionChecker(),
				$wikibaseRepo->getSummaryFormatter(),
				$this->getUser()
			),
			new RedirectCreationInteractor(
				$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
				$wikibaseRepo->getEntityStore(),
				$wikibaseRepo->getEntityPermissionChecker(),
				$wikibaseRepo->getSummaryFormatter(),
				$this->getUser(),
				new EditFilterHookRunner(
					$wikibaseRepo->getEntityTitleLookup(),
					$wikibaseRepo->getEntityContentFactory(),
					$this->getContext()
				)
			)
		);

	}

	public function setServices(
		EntityIdParser $idParser,
		ApiErrorReporter $errorReporter,
		ResultBuilder $resultBuilder,
		ItemMergeInteractor $interactorMerge,
		RedirectCreationInteractor $interactorRedirect
	) {
		$this->idParser = $idParser;
		$this->errorReporter = $errorReporter;
		$this->resultBuilder = $resultBuilder;
		$this->interactorMerge = $interactorMerge;
		$this->interactorRedirect = $interactorRedirect;
	}

	/**
	 * @param array $parameters
	 * @param string $name
	 *
	 * @return ItemId
	 *
	 * @throws UsageException if the given parameter is not a valiue ItemId
	 * @throws LogicException
	 */
	private function getItemIdParam( $parameters, $name ) {
		if ( !isset( $parameters[$name] ) ) {
			$this->errorReporter->dieError( 'Missing parameter: ' . $name, 'param-missing' );
		}

		$value = $parameters[$name];

		try {
			return new ItemId( $value );
		} catch ( InvalidArgumentException $ex ) {
			$this->errorReporter->dieError( $ex->getMessage(), 'invalid-entity-id' );
			throw new LogicException( 'ErrorReporter::dieError did not throw an exception' );
		}
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		try {
			$fromId = $this->getItemIdParam( $params, 'fromid' );
			$toId = $this->getItemIdParam( $params, 'toid' );

			$ignoreConflicts = $params['ignoreconflicts'];
			$summary = $params['summary'];

			if ( $ignoreConflicts === null ) {
				$ignoreConflicts = array();
			}

			$this->mergeItems( $fromId, $toId, $ignoreConflicts, $summary, $params['bot'] );
		} catch ( EntityIdParsingException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-entity-id' );
		} catch ( CreateRedirectException $ex ) {
			$this->handleException( $ex );
		} catch ( ItemMergeException $ex ) {
			$this->handleException( $ex );
		}
	}

	/**
	 * @param ItemId $fromId
	 * @param ItemId $toId
	 * @param array $ignoreConflicts
	 * @param string $summary
	 * @param bool $bot
	 * First merge items, then create a redirect
	 *
	 * @todo test if item is empty before calling createRedirect
	 */
	private function mergeItems( ItemId $fromId, ItemId $toId, array $ignoreConflicts, $summary, $bot ) {
		list( $newRevisionFrom, $newRevisionTo ) = $this->interactorMerge->mergeItems( $fromId, $toId, $ignoreConflicts, $summary, $bot );
		
		// @todo here: test if item is empty before calling createRedirect
		$this->interactorRedirect->createRedirect( $fromId, $toId, $bot );

		$this->resultBuilder->setValue( null, 'success', 1 );

		$this->addEntityToOutput( $newRevisionFrom, 'from' );
		$this->addEntityToOutput( $newRevisionTo, 'to' );
	}

	/**
	 * @param Exception $ex
	 *
	 * @throws UsageException always
	 */
	private function handleException( \Exception $ex ) {
		$cause = $ex->getPrevious();

		if ( $cause ) {
			$this->errorReporter->dieException( $cause, $ex->getErrorCode() );
		} else {
			$this->errorReporter->dieError( $ex->getMessage(), $ex->getErrorCode() );
		}
	}

	private function addEntityToOutput( EntityRevision $entityRevision, $name ) {
		$entityId = $entityRevision->getEntity()->getId();
		$revisionId = $entityRevision->getRevisionId();

		$this->resultBuilder->addBasicEntityInformation( $entityId, $name );

		$this->resultBuilder->setValue(
			$name,
			'lastrevid',
			(int)$revisionId
		);
	}

	/**
	 * @see ApiBase::needsToken
	 *
	 * @return string
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array(
			'fromid' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'toid' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'ignoreconflicts' => array(
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => ChangeOpsMerge::$conflictTypes,
				ApiBase::PARAM_REQUIRED => false,
			),
			'summary' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'bot' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false,
			),
			'token' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => 'true',
			)
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbmergeitems&fromid=Q42&toid=Q222' =>
				'apihelp-wbmergeitems-example-1',
			'action=wbmergeitems&fromid=Q555&toid=Q3' =>
				'apihelp-wbmergeitems-example-2',
			'action=wbmergeitems&fromid=Q66&toid=Q99&ignoreconflicts=sitelink' =>
				'apihelp-wbmergeitems-example-3',
			'action=wbmergeitems&fromid=Q66&toid=Q99&ignoreconflicts=sitelink|description' =>
				'apihelp-wbmergeitems-example-4',
		);
	}

	/**
	 * @see ApiBase::isWriteMode
	 *
	 * @return bool Always true.
	 */
	public function isWriteMode() {
		return true;
	}
}