<?php

namespace Wikibase\Api;

use ApiBase, User, Status, SiteList;
use Wikibase\ChangeOpAliases;
use Wikibase\ChangeOpClaim;
use Wikibase\ChangeOpDescription;
use Wikibase\ChangeOpLabel;
use Wikibase\ChangeOpMainSnak;
use Wikibase\ChangeOpQualifier;
use Wikibase\ChangeOps;
use Wikibase\ChangeOpSiteLink;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\EntityContentFactory;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\Property;
use Wikibase\Reference;
use Wikibase\ReferenceList;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SnakList;
use Wikibase\Statement;
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

	//todo allow merging of specific parts of an item (eg. sitelinks,aliases,claims)
	//todo allow optional deletion after merging
	//todo allow ignoring conflicts in titles and descriptions

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
		wfProfileIn( __METHOD__ );

		$this->getUser();
		$params = $this->extractRequestParams();
		if ( !array_key_exists( 'fromid', $params ) || !array_key_exists( 'toid', $params ) ){
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'You must provide a fromid and a toid' , 'param-missing' );
		}

		$entityIdParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		/**
		 * @var $fromId EntityId
		 * @var $toId EntityId
		 **/
		$fromId = $entityIdParser->parse( $params['fromid'] );
		$toId = $entityIdParser->parse( $params['toid'] );

		if( $fromId->getNumericId() === $toId->getNumericId() ){
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'You must provide unique ids' , 'param-invalid' );
		}

		$fromEntityContent = $entityContentFactory->getFromId( $fromId );
		$toEntityContent = $entityContentFactory->getFromId( $toId );

		if ( !( $fromEntityContent instanceof ItemContent && $toEntityContent instanceof ItemContent ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "One or more of the entities are not items", "not-item" );
		}

		$fromItem = $fromEntityContent->getItem();
		$toItem = $toEntityContent->getItem();

		$fromChangeOps = new ChangeOps();
		$toChangeOps = new ChangeOps();

		foreach( $fromItem->getLabels() as $langCode => $label ){
			$toLabel = $toItem->getLabel( $langCode );
			if( $toLabel === false || $toLabel === $label ){
				$fromChangeOps->add( new ChangeOpLabel( $langCode, '' ) );
				$toChangeOps->add( new ChangeOpLabel( $langCode, $label ) );
			} else {
				wfProfileOut( __METHOD__ );
				$this->dieUsage( "Conflicting labels for language {$langCode}", "merge-conflict");
			}
		}

		foreach( $fromItem->getDescriptions() as $langCode => $desc ){
			$toDescription = $toItem->getDescription( $langCode );
			if( $toDescription === false || $toDescription === $desc ){
				$fromChangeOps->add( new ChangeOpDescription( $langCode, '' ) );
				$toChangeOps->add( new ChangeOpDescription( $langCode, $desc ) );
			} else {
				wfProfileOut( __METHOD__ );
				$this->dieUsage( "Conflicting descriptions for language {$langCode}", "merge-conflict" );
			}
		}

		foreach( $fromItem->getAllAliases() as $langCode => $aliases ){
			$fromChangeOps->add( new ChangeOpAliases( $langCode, $aliases, 'remove' ) );
			$toChangeOps->add( new ChangeOpAliases( $langCode, $aliases, 'add' ) );
		}

		foreach( $fromItem->getSimpleSiteLinks() as $simpleSiteLink ){
			$siteId = $simpleSiteLink->getSiteId();
			if( !$toItem->hasLinkToSite( $siteId ) ){
				$fromChangeOps->add( new ChangeOpSiteLink( $siteId, '' ) );
				$toChangeOps->add( new ChangeOpSiteLink( $siteId, $simpleSiteLink->getPageName() ) );
			} else {
				wfProfileOut( __METHOD__ );
				$this->dieUsage( "Conflicting sitelinks for {$siteId}", "merge-conflict" );
			}
		}

		foreach( $fromItem->getClaims() as $fromClaim ){
			$fromChangeOps->add( new ChangeOpClaim( $fromClaim, 'remove' ) );
			$toChangeOps->add( new ChangeOpClaim( $fromClaim , 'add' ) );
		}

		$entityIdFormatter = WikibaseRepo::getDefaultInstance()->getEntityIdFormatter();

		$fromSummary = new Summary( $this->getModuleName(), 'from', null, array( $entityIdFormatter->format( $fromId ) ) );
		if ( !is_null( $params['summary'] ) ) {
			$fromSummary->setUserSummary( $params['summary'] );
		}

		$fromChangeOps->apply( $fromItem );
		$toChangeOps->apply( $toItem );

		$fromStatus = $this->attemptSaveEntity(
			$fromEntityContent,
			$fromSummary->toString()
		);
		$this->handleSaveStatus( $fromStatus );
		$this->addEntityToOutput( $fromEntityContent, $fromStatus, 'from' );

		if( $fromStatus->isGood() ){

			$toSummary = new Summary( $this->getModuleName(), 'to', null, array( $entityIdFormatter->format( $toId ) ) );
			if ( !is_null( $params['summary'] ) ) {
				$toSummary->setUserSummary( $params['summary'] );
			}

			$toStatus = $this->attemptSaveEntity(
				$toEntityContent,
				$toSummary->toString()
			);
			$this->handleSaveStatus( $toStatus );
			$this->addEntityToOutput( $toEntityContent, $toStatus, 'to' );

			$this->getResult()->addValue( null,	'success', 1 );
		}

		wfProfileOut( __METHOD__ );
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
			'api.php?action=wbmergeitems&fromid=q42&toid=q222' => 'Merges data from q42 into q222',
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
