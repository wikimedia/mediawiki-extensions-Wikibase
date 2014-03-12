<?php

namespace Wikibase\Api;

use ApiBase;
use InvalidArgumentException;
use Status;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOpsMerge;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\ItemContent;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SiteLinkLookup;
use Wikibase\Summary;
use Wikibase\TermIndex;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @todo allow merging of specific parts of an item only (eg. sitelinks,aliases,claims)
 * @todo allow optional deletion after merging (for admins)
 */
class MergeItems extends ApiWikibase {

	/**
	 * @var SiteLinkLookup
	 */
	private $sitelinkCache;

	/**
	 * @var TermIndex
	 */
	protected $termIndex;

	public function __construct( $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$this->sitelinkCache = WikibaseRepo::getDefaultInstance()->getStore()->newSiteLinkCache();
		$this->termIndex = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex();
	}

	/**
	 * @see \Wikibase\Api\Api::getRequiredPermissions()
	 *
	 * @param Entity $entity
	 * @param array $params
	 *
	 * @return array|\Status
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );
		$permissions[] = 'edit';
		$permissions[] = 'item-merge';
		return $permissions;
	}

	/**
	 * @see \ApiBase::execute()
	 */
	public function execute() {
		$user = $this->getUser();
		$params = $this->extractRequestParams();
		$this->validateParams( $params );

		$fromEntityRevision = $this->getEntityRevisionFromIdString( $params['fromid'] );
		$toEntityRevision = $this->getEntityRevisionFromIdString( $params['toid'] );

		$fromEntity = $fromEntityRevision->getEntity();
		$toEntity = $toEntityRevision->getEntity();

		$this->validateEntity( $fromEntity, $toEntity );

		$status = Status::newGood();
		$status->merge( $this->checkPermissions( $fromEntity, $user, $params ) );
		$status->merge( $this->checkPermissions( $toEntity, $user, $params ) );
		if( !$status->isGood() ){
			$this->dieUsage( $status->getMessage(), 'permissiondenied');
		}

		$ignoreConflicts = $this->getIgnoreConflicts( $params );

		/**
		 * @var Item $fromEntity
		 * @var Item $toEntity
		 */
		try{
			$changeOps = new ChangeOpsMerge(
				$fromEntity,
				$toEntity,
				new LabelDescriptionDuplicateDetector(
					$this->termIndex
				),
				$this->sitelinkCache,
				$ignoreConflicts
			);
			$changeOps->apply();
		}
		catch( InvalidArgumentException $e ) {
			$this->dieUsage( $e->getMessage(), 'param-invalid' );
		}
		catch( ChangeOpException $e ) {
			$this->dieUsage( $e->getMessage(), 'failed-save' );
		}

		$this->attemptSaveMerge( $fromEntity, $toEntity, $params );
	}

	protected function getIgnoreConflicts( $params ) {
		if( isset( $params['ignoreconflicts'] ) ){
			return $params['ignoreconflicts'];
		}
		return array();
	}

	protected function addEntityToOutput( Entity $entity, Status $status, $name ) {
		$this->getResultBuilder()->addBasicEntityInformation( $entity->getId(), $name );
		$this->getResultBuilder()->addRevisionIdFromStatusToResult( $status, $name );
	}

	private function getEntityRevisionFromIdString( $idString ) {
		try{
			$entityId = $this->idParser->parse( $idString );
			return $this->entityLookup->getEntityRevision( $entityId );
		}
		catch ( EntityIdParsingException $e ){
			$this->dieUsage( 'You must provide valid ids' , 'param-invalid' );
		}
		return null;
	}

	/**
	 * @param Entity|null $fromEntity
	 * @param Entity|null $toEntity
	 */
	private function validateEntity( $fromEntity, $toEntity) {
		if( $fromEntity === null || $toEntity === null ){
			$this->dieUsage( 'One of more of the ids provided do not exist' , 'no-such-entity-id' );
		}

		if ( !( $fromEntity instanceof Item && $toEntity instanceof Item ) ) {
			$this->dieUsage( 'One or more of the entities are not items', 'not-item' );
		}

		if( $toEntity->getId()->equals( $fromEntity->getId() ) ){
			$this->dieUsage( 'You must provide unique ids' , 'param-invalid' );
		}
	}

	/**
	 * @param string[] $params
	 */
	private function validateParams( array $params ) {
		if ( empty( $params['fromid'] ) || empty( $params['toid'] ) ){
			$this->dieUsage( 'You must provide a fromid and a toid' , 'param-missing' );
		}
	}

	/**
	 * @param string $direction either 'from' or 'to'
	 * @param ItemId $getId
	 * @param array $params
	 * @return Summary
	 */
	private function getSummary( $direction, $getId, $params ) {
		$summary = new Summary( $this->getModuleName(), $direction, null, array( $getId->getSerialization() ) );
		if ( !is_null( $params['summary'] ) ) {
			$summary->setUserSummary( $params['summary'] );
		}
		return $summary;
	}

	/**
	 * @param Item $fromItem
	 * @param Item $toItem
	 * @param $params
	 */
	private function attemptSaveMerge( Item $fromItem, Item $toItem, $params ) {
		$toSummary = $this->getSummary( 'to', $toItem->getId(), $params );

		$fromStatus = $this->attemptSaveEntity(
			$fromItem,
			$this->formatSummary( $toSummary )
		);

		$this->handleSaveStatus( $fromStatus );
		$this->addEntityToOutput( $fromItem, $fromStatus, 'from' );

		if( $fromStatus->isGood() ) {
			$fromSummary = $this->getSummary( 'from', $fromItem->getId(), $params );

			$toStatus = $this->attemptSaveEntity(
				$toItem,
				$this->formatSummary( $fromSummary )
			);
			$this->handleSaveStatus( $toStatus );
			$this->addEntityToOutput( $toItem, $toStatus, 'to' );

			$this->getResultBuilder()->markSuccess( 1 );
		} else {
			//todo if the second result is not a success we should probably undo the first change
			$this->getResultBuilder()->markSuccess( 0 );
		}
	}

	/**
	 * @see \ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'not-item', 'info' => $this->msg( 'wikibase-api-not-item' )->text() ),
			array( 'code' => 'no-such-entity-id', 'info' => $this->msg( 'wikibase-api-no-such-entity-id' )->text() ),
		) );
	}

	/**
	 * @see \ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			array(
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
			)
		);
	}

	/**
	 * @see \ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			array(
				'fromid' => array( 'The id to merge from' ),
				'toid' => array( 'The id to merge to' ),
				'ignoreconflicts' => array( 'Array of elements of the item to ignore conflicts for, can only contain values of "label" and or "description" and or "sitelink"' ),
				'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
				'summary' => array( 'Summary for the edit.',
					"Will be prepended by an automatically generated comment. The length limit of the
					autocomment together with the summary is 260 characters. Be aware that everything above that
					limit will be cut off."
				),
			)
		);
	}

	/**
	 * @see \ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to merge multiple items.'
		);
	}

	/**
	 * @see \ApiBase::getExamples()
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbmergeitems&fromid=Q42&toid=Q222' =>
				'Merges data from Q42 into Q222',
			'api.php?action=wbmergeitems&fromid=Q555&toid=Q3' =>
				'Merges data from Q555 into Q3',
			'api.php?action=wbmergeitems&fromid=Q66&toid=Q99&ignoreconflicts=label' =>
				'Merges data from Q66 into Q99 ignoring any conflicting labels',
			'api.php?action=wbmergeitems&fromid=Q66&toid=Q99&ignoreconflicts=label|description' =>
				'Merges data from Q66 into Q99 ignoring any conflicting labels and descriptions',
		);
	}

	/**
	 * @see ApiBase::isWriteMode
	 * @return bool true
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see ApiBase::mustBePosted
	 */
	public function mustBePosted() {
		return true;
	}

}
