<?php

namespace Wikibase\Api;

use ApiBase, User, Status, SiteList;
use ValueParsers\ParseException;
use Wikibase\ChangeOpException;
use Wikibase\ChangeOpsMerge;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityContentFactory;
use Wikibase\EntityContent;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\Property;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\Utils;

/**
 * @since 0.5
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
		$this->getUser();
		$params = $this->extractRequestParams();
		$this->validateParams( $params );

		$fromEntityContent = $this->getEntityContentFromIdString( $params['fromid'] );
		$toEntityContent = $this->getEntityContentFromIdString( $params['toid'] );
		$this->validateEntityContents( $fromEntityContent, $toEntityContent );

		try{
			$changeOps = new ChangeOpsMerge( $fromEntityContent, $toEntityContent );
			$changeOps->apply();
		}
		catch( ChangeOpException $e ){
			$this->dieUsage( $e->getMessage(), 'failed-save');
		}

		$this->attemptSaveMerge( $fromEntityContent, $toEntityContent, $params );
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

	private function getEntityContentFromIdString( $idString ) {
		$entityIdParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$entityContent = null;

		try{
			$entityId = $entityIdParser->parse( $idString );
			$entityContent = $entityContentFactory->getFromId( $entityId );
		}
		catch ( ParseException $e ){
			$this->dieUsage( 'You must provide valid ids' , 'param-invalid' );
		}

		if( $entityContent === null ){
			$this->dieUsage( 'You must provide valid ids' , 'param-invalid' );
		}

		return $entityContent;
	}

	/**
	 * @param EntityContent $fromEntityContent
	 * @param EntityContent $toEntityContent
	 */
	private function validateEntityContents( $fromEntityContent, $toEntityContent ) {
		if ( !( $fromEntityContent instanceof ItemContent && $toEntityContent instanceof ItemContent ) ) {
			$this->dieUsage( "One or more of the entities are not items", "not-item" );
		}

		if( $fromEntityContent->getEntity()->getId()->getPrefixedId() === $toEntityContent->getEntity()->getId()->getPrefixedId() ){
			$this->dieUsage( 'You must provide unique ids' , 'param-invalid' );
		}
	}

	private function validateParams( $params ) {
		if ( empty( $params['fromid'] ) || empty( $params['toid'] ) ){
			$this->dieUsage( 'You must provide a fromid and a toid' , 'param-missing' );
		}
	}

	/**
	 * @param $direction
	 * @param $getId
	 * @param $params
	 * @return Summary
	 */
	private function getSummary( $direction, $getId, $params ) {
		$entityIdFormatter = WikibaseRepo::getDefaultInstance()->getEntityIdFormatter();
		$summary = new Summary( $this->getModuleName(), $direction, null, array( $entityIdFormatter->format( $getId ) ) );
		if ( !is_null( $params['summary'] ) ) {
			$summary->setUserSummary( $params['summary'] );
		}
		return $summary;
	}

	private function attemptSaveMerge( ItemContent $fromItemContent, ItemContent $toItemContent, $params ) {
		$fromStatus = $this->attemptSaveEntity(
			$fromItemContent,
			$this->getSummary( 'from', $fromItemContent->getItem()->getId(), $params )->toString()
		);
		$this->handleSaveStatus( $fromStatus );
		$this->addEntityToOutput( $fromItemContent, $fromStatus, 'from' );

		if( $fromStatus->isGood() ){

			$toStatus = $this->attemptSaveEntity(
				$toItemContent,
				$this->getSummary( 'to', $toItemContent->getItem()->getId(), $params )->toString()
			);
			$this->handleSaveStatus( $toStatus );
			$this->addEntityToOutput( $toItemContent, $toStatus, 'to' );

			$this->getResult()->addValue( null,	'success', 1 );

			//todo if the second result is not a success we should probably undo the first change
		}
	}

	/**
	 * @see \ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'not-item', 'info' => $this->msg( 'wikibase-api-not-item' )->text() ),
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

}
