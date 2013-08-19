<?php

namespace Wikibase\Api;

use ApiBase, User, Status, SiteList;
use ValueParsers\ParseException;
use Wikibase\ChangeOpAliases;
use Wikibase\ChangeOpClaim;
use Wikibase\ChangeOpDescription;
use Wikibase\ChangeOpLabel;
use Wikibase\ChangeOps;
use Wikibase\ChangeOpSiteLink;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityContentFactory;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Property;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\Utils;

/**
 * @since 0.4
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class MergeItems extends ApiWikibase {

	//todo allow merging of specific parts of an item only (eg. sitelinks,aliases,claims)
	//todo allow optional deletion after merging (for admins)

	/**
	 * @see \Wikibase\Api\Api::getRequiredPermissions()
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
		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$fromContent = $this->getEntityContentFromString( $params['fromid'] );
		$toContent = $this->getEntityContentFromString( $params['toid'] );

		$this->validateItemContents( $fromContent, $toContent );

		$this->applyChangeOps(
			$this->getMergeItemsChangeops( $fromContent, $toContent ),
			$fromContent->getItem(),
			$toContent->getItem()
		);

		$this->attemptSaveMerge( $fromContent, $toContent, $params );
	}

	protected function addEntityToOutput( EntityContent $entityContent, Status $status, $name ) {
		$formatter = WikibaseRepo::getDefaultInstance()->getEntityIdFormatter();

		$this->getResult()->addValue(
			$name,
			'id',
			$formatter->format( $entityContent->getEntity()->getId() )
		);

		$this->getResult()->addValue(
			$name,
			'type', $entityContent->getEntity()->getType()
		);

		$this->addRevisionIdFromStatusToResult( $name, 'lastrevid', $status );
	}

	/**
	 * @see \ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'not-item', 'info' => $this->msg( 'wikibase-api-not-item' )->text() ),
			array( 'code' => 'merge-conflict', 'info' => 'A conflict occurred when trying to merge the items' ),
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
			'api.php?action=wbmergeitems&fromid=Q42&toid=Q222' => 'Merges data from Q42 into Q222',
			'api.php?action=wbmergeitems&fromid=Q555&toid=Q3' => 'Merges data from Q555 into Q3',
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
	 * @param array $params
	 */
	private function validateParameters( $params ) {
		if ( empty( $params['fromid'] ) || empty( $params['toid'] ) ){
			$this->dieUsage( 'You must provide a fromid and a toid' , 'param-missing' );
		}
	}

	/**
	 * @param $entityIdParam
	 * @return ItemId|null
	 */
	public function getEntityIdFromString( $entityIdParam ) {
		$entityIdParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		try {
			return $entityIdParser->parse( $entityIdParam );
		} catch ( ParseException $parseException ) {
			$this->dieUsage( 'Invalid entity ID: ParseException', 'invalid-entity-id' );
		}
		return null;
	}

	/**
	 * @param ItemContent $fromContent
	 * @param ItemContent $toContent
	 * @return array
	 */
	private function mergeLabels( $fromContent, $toContent ) {
		$changeOps = array();
		foreach( $fromContent->getEntity()->getLabels() as $langCode => $label ){
			$toLabel = $toContent->getEntity()->getLabel( $langCode );
			if( $toLabel === false || $toLabel === $label ){
				$changeOps['from'][] = new ChangeOpLabel( $langCode, null );
				$changeOps['to'][] = new ChangeOpLabel( $langCode, $label );
			} else {
				//todo add the option to merge conflicting labels into the aliases
				$this->dieUsage( "Conflicting labels for language {$langCode}", "merge-conflict");
			}
		}
		return $changeOps;
	}

	/**
	 * @param ItemContent $fromContent
	 * @param ItemContent $toContent
	 * @return array
	 */
	private function mergeDescriptions( $fromContent, $toContent ) {
		$changeOps = array();
		foreach( $fromContent->getEntity()->getDescriptions() as $langCode => $desc ){
			$toDescription = $toContent->getEntity()->getDescription( $langCode );
			if( $toDescription === false || $toDescription === $desc ){
				$changeOps['from'][] = new ChangeOpDescription( $langCode, null );
				$changeOps['to'][] = new ChangeOpDescription( $langCode, $desc );
			} else {
				//todo add the option to ignore description conflicts, or prioritise one
				$this->dieUsage( "Conflicting descriptions for language {$langCode}", "merge-conflict" );
			}
		}
		return $changeOps;
	}

	/**
	 * @param ItemContent $fromContent
	 * @return array
	 */
	private function mergeAliases( $fromContent ) {
		$changeOps = array();
		foreach( $fromContent->getEntity()->getAllAliases() as $langCode => $aliases ){
			$changeOps['from'][] = new ChangeOpAliases( $langCode, $aliases, 'remove' );
			$changeOps['to'][] = new ChangeOpAliases( $langCode, $aliases, 'add' );
		}
		return $changeOps;
	}

	/**
	 * @param ItemContent $fromContent
	 * @param ItemContent $toContent
	 * @return array
	 */
	private function mergeSitelinks( $fromContent, $toContent ) {
		$changeOps = array();
		foreach( $fromContent->getEntity()->getSimpleSiteLinks() as $simpleSiteLink ){
			$siteId = $simpleSiteLink->getSiteId();
			if( !$toContent->getEntity()->hasLinkToSite( $siteId ) ){
				$changeOps['from'][] = new ChangeOpSiteLink( $siteId, null );
				$changeOps['to'][] = new ChangeOpSiteLink( $siteId, $simpleSiteLink->getPageName() );
			} else {
				$this->dieUsage( "Conflicting sitelinks for {$siteId}", "merge-conflict" );
			}
		}
		return $changeOps;
	}

	/**
	 * @param ItemContent $fromContent
	 * @param ItemContent $toContent
	 * @return array
	 */
	private function mergeClaims( $fromContent, $toContent ) {
		$changeOps = array();
		foreach( $fromContent->getEntity()->getClaims() as $fromClaim ){
			$changeOps['from'][] = new ChangeOpClaim( $fromClaim, 'remove', new ClaimGuidGenerator( $fromContent->getEntity()->getId() ) );
			$fromClaim->setGuid( null );
			$changeOps['to'][] = new ChangeOpClaim( $fromClaim , 'add', new ClaimGuidGenerator( $toContent->getEntity()->getId() ) );
		}
		return $changeOps;
	}

	/**
	 * @param array $changeOps
	 * @param Item $fromItem
	 * @param Item $toItem
	 */
	private function applyChangeOps( $changeOps , $fromItem, $toItem ) {
		$fromChangeOps = new ChangeOps( $changeOps['from'] );
		$fromChangeOps->apply( $fromItem );
		$toChangeOps = new ChangeOps( $changeOps['to'] );
		$toChangeOps->apply($toItem );
	}

	/**
	 * @param string $fromid
	 * @return ItemContent
	 */
	private function getEntityContentFromString( $fromid ) {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		$id = $this->getEntityIdFromString( $fromid );
		$entityContent = $entityContentFactory->getFromId( $id );

		if ( !( $entityContent instanceof ItemContent ) ) {
			$this->dieUsage( "Entity is nto an item", "not-item" );
		}
		return $entityContent;
	}

	/**
	 * @param ItemContent $fromContent
	 * @param ItemContent $toContent
	 */
	private function validateItemContents( $fromContent, $toContent ) {
		if( $fromContent->getEntity()->getId()->getPrefixedId() === $toContent->getEntity()->getId()->getPrefixedId() ){
			$this->dieUsage( 'You must provide unique ids' , 'param-invalid' );
		}

		if ( !( $fromContent instanceof ItemContent && $toContent instanceof ItemContent ) ) {
			$this->dieUsage( "One or more of the entities are not items", "not-item" );
		}
	}

	/**
	 * @param ItemContent $fromContent
	 * @param ItemContent $toContent
	 * @return array
	 */
	private function getMergeItemsChangeops( $fromContent, $toContent ) {
		$changeOps = array( 'from' => array(), 'to' => array() );
		$changeOps = array_merge_recursive( $changeOps, $this->mergeLabels( $fromContent, $toContent ) );
		$changeOps = array_merge_recursive( $changeOps, $this->mergeDescriptions( $fromContent, $toContent ) );
		$changeOps = array_merge_recursive( $changeOps, $this->mergeAliases( $fromContent ) );
		$changeOps = array_merge_recursive( $changeOps, $this->mergeSitelinks( $fromContent, $toContent ) );
		$changeOps = array_merge_recursive( $changeOps, $this->mergeClaims( $fromContent, $toContent ) );
		return $changeOps;
	}

	/**
	 * @param string $direction
	 * @param Item $getEntity
	 * @param array $params
	 * @return \Wikibase\Summary
	 */
	private function getSummary( $direction, $getEntity, $params ) {
		$entityIdFormatter = WikibaseRepo::getDefaultInstance()->getEntityIdFormatter();

		$summary = new Summary( $this->getModuleName(), $direction, null, array( $entityIdFormatter->format( $getEntity->getId() ) ) );
		if ( !is_null( $params['summary'] ) ) {
			$summary->setUserSummary( $params['summary'] );
		}
		return $summary;
	}

	/**
	 * @param ItemContent $fromContent
	 * @param ItemContent $toContent
	 * @param array $params
	 */
	private function attemptSaveMerge( $fromContent, $toContent, $params ) {
		$fromStatus = $this->attemptSaveEntity( $fromContent, $this->getSummary( 'from', $fromContent->getEntity(), $params )->toString() );
		$this->handleSaveStatus( $fromStatus );
		$this->addEntityToOutput( $fromContent, $fromStatus, 'from' );

		if( $fromStatus->isGood() ){

			$toStatus = $this->attemptSaveEntity( $toContent, $this->getSummary( 'to', $toContent->getEntity(), $params )->toString() );
			$this->handleSaveStatus( $toStatus );
			$this->addEntityToOutput( $toContent, $toStatus, 'to' );

			//todo if the second result is not a success we should probably undo the first change
			$this->getResult()->addValue( null,	'success', 1 );
		}
	}

}
