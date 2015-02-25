<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use LogicException;
use UsageException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Repo\Interactors\ItemMergeException;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\WikibaseRepo;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Daniel Kinzler
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
	private $interactor;

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
			)
		);

	}

	public function setServices(
		EntityIdParser $idParser,
		ApiErrorReporter $errorReporter,
		ResultBuilder $resultBuilder,
		ItemMergeInteractor $interactor
	) {
		$this->idParser = $idParser;
		$this->errorReporter = $errorReporter;
		$this->resultBuilder = $resultBuilder;
		$this->interactor = $interactor;
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
		} catch ( \InvalidArgumentException $ex ) {
			$this->errorReporter->dieError( $ex->getMessage(), 'invalid-entity-id' );
			throw new \LogicException( 'ErrorReporter::dieError did not throw an exception' );
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

			$this->mergeItems( $fromId, $toId, $ignoreConflicts, $summary );
		} catch ( EntityIdParsingException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-entity-id' );
		} catch ( ItemMergeException $ex ) {
			$this->handleItemMergeException( $ex );
		}
	}

	/**
	 * @param ItemId $fromId
	 * @param ItemId $toId
	 * @param array $ignoreConflicts
	 * @param string $summary
	 */
	private function mergeItems( ItemId $fromId, ItemId $toId, array $ignoreConflicts, $summary ) {
		list( $newRevisionFrom, $newRevisionTo ) = $this->interactor->mergeItems( $fromId, $toId, $ignoreConflicts, $summary );

		$this->resultBuilder->setValue( null, 'success', 1 );

		$this->addEntityToOutput( $newRevisionFrom, 'from' );
		$this->addEntityToOutput( $newRevisionTo, 'to' );
	}

	/**
	 * @param ItemMergeException $ex
	 *
	 * @throws UsageException always
	 */
	private function handleItemMergeException( ItemMergeException $ex ) {
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
			intval( $revisionId )
		);
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
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
			),
			'summary' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'token' => null,
			'bot' => false
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
			'action=wbmergeitems&fromid=Q66&toid=Q99&ignoreconflicts=sitelinks' =>
				'apihelp-wbmergeitems-example-3',
			'action=wbmergeitems&fromid=Q66&toid=Q99&ignoreconflicts=sitelinks|description' =>
				'apihelp-wbmergeitems-example-4',
		);
	}

	/**
	 * @see ApiBase::isWriteMode
	 * @return bool true
	 */
	public function isWriteMode() {
		return true;
	}

}
