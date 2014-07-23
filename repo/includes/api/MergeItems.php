<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use InvalidArgumentException;
use Status;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

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
	 * @var SiteLinkChangeOpFactory
	 */
	protected $changeOpFactory;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$factoryProvider = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider();
		$this->changeOpFactory = $factoryProvider->getMergeChangeOpFactory();
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

		if ( !$fromEntityRevision || !$toEntityRevision ) {
			$this->dieError( 'Item not found for ID.', 'no-such-entity' );
		}

		$fromEntity = $fromEntityRevision->getEntity();
		$toEntity = $toEntityRevision->getEntity();

		$this->validateEntity( $fromEntity, $toEntity );

		$status = Status::newGood();
		$status->merge( $this->checkPermissions( $fromEntity, $user, $params ) );
		$status->merge( $this->checkPermissions( $toEntity, $user, $params ) );
		if( !$status->isGood() ){
			$this->dieStatus( $status, 'permissiondenied' );
		}

		$ignoreConflicts = $this->getIgnoreConflicts( $params );

		/**
		 * @var Item $fromEntity
		 * @var Item $toEntity
		 */
		try{
			$changeOps = $this->changeOpFactory->newMergeOps(
				$fromEntity,
				$toEntity,
				$ignoreConflicts
			);

			$changeOps->apply();
		}
		catch( InvalidArgumentException $e ) {
			$this->dieException( $e, 'param-invalid' );
		}
		catch( ChangeOpException $e ) {
			$this->dieException( $e, 'failed-save' ); //FIXME: change to modification-failed
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
			$this->dieError( 'You must provide valid ids' , 'param-invalid' );
		}
		return null;
	}

	/**
	 * @param Entity $fromEntity
	 * @param Entity $toEntity
	 */
	private function validateEntity( $fromEntity, $toEntity) {
		if ( !( $fromEntity instanceof Item && $toEntity instanceof Item ) ) {
			$this->dieError( 'One or more of the entities are not items', 'not-item' );
		}

		if( $toEntity->getId()->equals( $fromEntity->getId() ) ){
			$this->dieError( 'You must provide unique ids' , 'param-invalid' );
		}
	}

	/**
	 * @param string[] $params
	 */
	private function validateParams( array $params ) {
		if ( empty( $params['fromid'] ) || empty( $params['toid'] ) ){
			$this->dieError( 'You must provide a fromid and a toid' , 'param-missing' );
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
				'bot' => false
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
				'bot' => array( 'Mark this edit as bot',
					'This URL flag will only be respected if the user belongs to the group "bot".'
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

}
