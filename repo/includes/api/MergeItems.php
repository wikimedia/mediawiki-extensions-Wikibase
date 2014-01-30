<?php

namespace Wikibase\Api;

use ApiBase;
use InvalidArgumentException;
use Status;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOpsMerge;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityContent;
use Wikibase\ItemContent;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StoreFactory;
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
	 * @see \Wikibase\Api\Api::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( EntityContent $entityContent, array $params ) {
		$permissions = parent::getRequiredPermissions( $entityContent, $params );
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

		$fromEntityContent = $this->getEntityContentFromIdString( $params['fromid'] );
		$toEntityContent = $this->getEntityContentFromIdString( $params['toid'] );
		$this->validateEntityContents( $fromEntityContent, $toEntityContent );

		$status = Status::newGood();
		$status->merge( $this->checkPermissions( $fromEntityContent, $user, $params ) );
		$status->merge( $this->checkPermissions( $toEntityContent, $user, $params ) );
		if( !$status->isGood() ){
			$this->dieUsage( $status->getMessage(), 'permissiondenied');
		}

		$ignoreConflicts = $this->getIgnoreConflicts( $params );
		$sitelinkCache = WikibaseRepo::getDefaultInstance()->getStore()->newSiteLinkCache();
		$termIndex = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex();

		/**
		 * @var ItemContent $fromEntityContent
		 * @var ItemContent $toEntityContent
		 */
		try{
			$changeOps = new ChangeOpsMerge(
				$fromEntityContent,
				$toEntityContent,
				new LabelDescriptionDuplicateDetector(
					$termIndex
				),
				$sitelinkCache,
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

		$this->attemptSaveMerge( $fromEntityContent, $toEntityContent, $params );
	}

	protected function getIgnoreConflicts( $params ) {
		if( isset( $params['ignoreconflicts'] ) ){
			return $params['ignoreconflicts'];
		}
		return array();
	}

	protected function addEntityToOutput( EntityContent $entityContent, Status $status, $name ) {
		$this->getResultBuilder()->addBasicEntityInformation( $entityContent->getEntity()->getId(), $name );
		$this->getResultBuilder()->addRevisionIdFromStatusToResult( $status, $name );
	}

	private function getEntityContentFromIdString( $idString ) {
		$entityIdParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		try{
			$entityId = $entityIdParser->parse( $idString );
			return $entityContentFactory->getFromId( $entityId );
		}
		catch ( EntityIdParsingException $e ){
			$this->dieUsage( 'You must provide valid ids' , 'param-invalid' );
		}
		return null;
	}

	/**
	 * @param EntityContent|null $fromEntityContent
	 * @param EntityContent|null $toEntityContent
	 */
	private function validateEntityContents( $fromEntityContent, $toEntityContent ) {
		if( $fromEntityContent === null || $toEntityContent === null ){
			$this->dieUsage( 'One of more of the ids provided do not exist' , 'no-such-entity-id' );
		}

		if ( !( $fromEntityContent instanceof ItemContent && $toEntityContent instanceof ItemContent ) ) {
			$this->dieUsage( 'One or more of the entities are not items', 'not-item' );
		}

		if( $toEntityContent->getEntity()->getId()->equals( $fromEntityContent->getEntity()->getId() ) ){
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

	private function attemptSaveMerge( ItemContent $fromItemContent, ItemContent $toItemContent, $params ) {
		$toSummary = $this->getSummary( 'to', $toItemContent->getItem()->getId(), $params );

		$fromStatus = $this->attemptSaveEntity(
			$fromItemContent,
			$this->formatSummary( $toSummary )
		);

		$this->handleSaveStatus( $fromStatus );
		$this->addEntityToOutput( $fromItemContent, $fromStatus, 'from' );

		if( $fromStatus->isGood() ) {
			$fromSummary = $this->getSummary( 'from', $fromItemContent->getItem()->getId(), $params );

			$toStatus = $this->attemptSaveEntity(
				$toItemContent,
				$this->formatSummary( $fromSummary )
			);
			$this->handleSaveStatus( $toStatus );
			$this->addEntityToOutput( $toItemContent, $toStatus, 'to' );

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
