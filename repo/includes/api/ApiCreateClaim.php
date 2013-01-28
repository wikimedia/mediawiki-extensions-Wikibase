<?php

namespace Wikibase;
use ApiBase, MWException;

/**
 * API module for creating claims.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.2
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiCreateClaim extends Api implements ApiSummary {

	// TODO: example
	// TODO: rights
	// TODO: conflict detection

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.2
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$this->checkParameterRequirements();

		$params = $this->extractRequestParams();

		$entityContent = $this->getEntityContent();

		$claim = $this->addClaim( $entityContent->getEntity() );

		$this->summary = new \Wikibase\Summary( 'wbcreateclaim', $params['snaktype'] );
		$this->summary->removeFormat( \Wikibase\Summary::USE_SUMMARY );
		$this->summary->addAutoCommentArgs( $claim->getMainSnak()->getPropertyId() . '/' . $claim->getGuid() );
		$this->summary->addAutoSummaryArgs( Summary::pickValuesFromParams( $params, 'value' ) );

		$this->saveChanges( $entityContent );

		$this->outputClaim( $claim );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Constructs a new Claim based on the arguments provided to the API,
	 * adds it to the Entity and saves it.
	 *
	 * On success, the added Claim is returned as part of the Status object.
	 *
	 * @since 0.2
	 *
	 * @param Entity $entity
	 *
	 * @return Claim
	 */
	protected function addClaim( Entity $entity ) {
		wfProfileIn( __METHOD__ );

		// It is possible we get an exception from this method because of specifying
		// a non existing-property, specifying an entity id for an entity with wrong
		// entity type or providing an invalid DataValue.
		try {
			$snak = $this->getSnakInstance();
		}
		catch ( \Exception $ex ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsageMsg( $ex->getMessage() );
		}

		$claim = $entity->newClaim( $snak );

		$entity->addClaim( $claim );

		wfProfileOut( __METHOD__ );
		return $claim;
	}

	/**
	 * @since 0.3
	 *
	 * @param EntityContent $content
	 */
	protected function saveChanges( EntityContent $content ) {
		$params = $this->extractRequestParams();

		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$baseRevisionId = $baseRevisionId > 0 ? $baseRevisionId : false;
		$editEntity = new EditEntity( $content, $this->getUser(), $baseRevisionId, $this->getContext() );

		$status = $editEntity->attemptSave(
			$this->summary->toString(),
			EDIT_UPDATE,
			isset( $params['token'] ) ? $params['token'] : ''
		);

		if ( !$status->isOK() ) {
			$this->dieUsage( 'Failed to save the change', 'createclaim-save-failed' );
		}

		$statusValue = $status->getValue();

		if ( isset( $statusValue['revision'] ) ) {
			$this->getResult()->addValue(
				'pageinfo',
				'lastrevid',
				(int)$statusValue['revision']->getId()
			);
		}
	}

	/**
	 * Checks if the required parameters are set and the ones that make no sense given the
	 * snaktype value are not set.
	 *
	 * @since 0.2
	 */
	protected function checkParameterRequirements() {
		$params = $this->extractRequestParams();

		if ( $params['snaktype'] == 'value' XOR isset( $params['value'] ) ) {
			if ( $params['snaktype'] == 'value' ) {
				$this->dieUsage( 'A value needs to be provided when creating a claim with PropertyValueSnak snak', 'claim-value-missing' );
			}
			else {
				$this->dieUsage( 'You cannot provide a value when creating a claim with no PropertyValueSnak as main snak', 'claim-value-set' );
			}
		}

		if ( !isset( $params['property'] ) ) {
			$this->dieUsage( 'A property ID needs to be provided when creating a claim with a Snak', 'claim-property-id-missing' );
		}
	}

	/**
	 * @since 0.2
	 *
	 * @return EntityContent
	 */
	protected function getEntityContent() {
		$params = $this->extractRequestParams();

		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;

		$entityId = EntityId::newFromPrefixedId( $params['entity'] );
		$entityTitle = $entityId ? EntityContentFactory::singleton()->getTitleForId( $entityId ) : null;
		$entityContent = $entityTitle === null ? null : $this->loadEntityContent( $entityTitle, $baseRevisionId );

		if ( $entityContent === null ) {
			$this->dieUsage( 'Entity not found, snak not created', 'entity-not-found' );
		}

		return $entityContent;
	}

	/**
	 * @since 0.2
	 *
	 * @return Snak
	 * @throws MWException
	 */
	protected function getSnakInstance() {
		$params = $this->extractRequestParams();

		$factory = new SnakFactory();

		$libRegistry = new LibRegistry( Settings::singleton() );
		$parseResult = $libRegistry->getEntityIdParser()->parse( $params['property'] );

		if ( !$parseResult->isValid() ) {
			throw new MWException( $parseResult->getError()->getText() );
		}

		return $factory->newSnak(
			$parseResult->getValue(),
			$params['snaktype'],
			isset( $params['value'] ) ? \FormatJson::decode( $params['value'], true ) : null
		);
	}

	/**
	 * @since 0.3
	 *
	 * @param Claim $claim
	 */
	protected function outputClaim( Claim $claim ) {
		$serializer = new ClaimSerializer();
		$serializer->getOptions()->setIndexTags( $this->getResult()->getIsRawMode() );

		$this->getResult()->addValue(
			null,
			'claim',
			$serializer->getSerialized( $claim )
		);
	}

	/**
	 * @see ApiBase::getAllowedParams
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'entity' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'snaktype' => array(
				ApiBase::PARAM_TYPE => array( 'value', 'novalue', 'somevalue' ),
				ApiBase::PARAM_REQUIRED => true,
			),
			'property' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
			),
			'value' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
			),
			'token' => null,
			'baserevid' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
		);
	}

	/**
	 * @see ApiBase::getParamDescription
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'entity' => 'Id of the entity you are adding the claim to',
			'property' => 'Id of the snaks property',
			'value' => 'Value of the snak when creating a claim with a snak that has a value',
			'snaktype' => 'The type of the snak',
			'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
			'baserevid' => array( 'The numeric identifier for the revision to base the modification on.',
				"This is used for detecting conflicts during save."
			),
		);
	}

	/**
	 * @see ApiBase::getDescription
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module for creating Wikibase claims.'
		);
	}

	/**
	 * @see ApiBase::getExamples
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=createclaim&entity=q42&property=p9001&snaktype=novalue&token=foobar&baserevid=7201010',
			'api.php?action=createclaim&entity=q42&property=p9001&snaktype=value&value={"entity-type":"item","numeric-id":1}&token=foobar&baserevid=7201010',
			// 'ex' => 'desc'
		);
	}

	/**
	 * @see ApiBase::getHelpUrls
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbcreateclaim';
	}

	/**
	 * @see ApiBase::getVersion
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
	}

	/**
	 * @see  ApiSummary::getTextForComment()
	 */
	public function getTextForComment( array $params, $plural = 1 ) {
		return Summary::formatAutoComment(
			'wbcreateclaim-' . $params['snaktype'],
			array(
				/*plural */ (int)isset( $params['value'] ),
				isset( $params['property'] ) ? $params['property'] : ''
			)
		);
	}

	/**
	 * @see  ApiSummary::getTextForSummary()
	 */
	public function getTextForSummary( array $params ) {
		return Summary::formatAutoSummary(
			Summary::pickValuesFromParams( $params, 'value' )
		);
	}

	/**
	 * @see \ApiBase::needsToken()
	 */
	public function needsToken() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens' ) : true;
	}

	/**
	 * @see \ApiBase::mustBePosted()
	 */
	public function mustBePosted() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost' ) : true;
	}

	/**
	 * @see \ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
	}

}
