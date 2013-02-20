<?php

namespace Wikibase;
use ApiBase, MWException;

/**
 * Base module for handling claims.
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
 * @since 0.4
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class ApiModifyClaim extends Api {

	/**
	 * Create a new Summary instance suitable for representing the action performed by this module.
	 *
	 * @param array $params
	 *
	 * @return Summary
	 */
	protected function createSummary( array $params ) {
		$summary = new Summary( $this->getModuleName() );
		return $summary;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityContent $content The content to save
	 * @param int           $flags   Edit flags, e.g. EDIT_NEW
	 * @param Summary       $summary The summary to set.
	 *
	 * @return void
	 */
	protected function saveChanges( EntityContent $content, Summary $summary ) {
		$editEntity = $this->attemptSaveEntity( $content,
			$summary,
			EDIT_UPDATE );

		$revision = $editEntity->getNewRevision();

		if ( $revision ) {
			$this->getResult()->addValue(
				'pageinfo',
				'lastrevid',
				$revision->getId()
			);
		}
	}

	/**
	 * Loads the entity containing the Claim with the given GUID.
	 * If the baserevid parameter is specified, the specified revision of the entity is loaded.
	 *
	 * @since 0.4
	 *
	 * @param $claimGuid string
	 *
	 * @return EntityContent
	 */
	protected function getEntityContentForClaim( $claimGuid ) {
		$entityId = Entity::getIdFromClaimGuid( $claimGuid );
		$content = $this->getEntityContent( $entityId );

		return $content;
	}

	/**
	 * Loads the specified entity.
	 * If the baserevid parameter is specified, the specified revision of the entity is loaded.
	 *
	 * @since 0.4
	 *
	 * @param $entityId string|EntityId Entitiy ID as a string or object
	 *
	 * @return EntityContent
	 */
	protected function getEntityContent( $entityId ) {
		$params = $this->extractRequestParams();

		if ( is_string( $entityId ) ) {
			$entityId = EntityId::newFromPrefixedId( $entityId );
		}

		$entityTitle = EntityContentFactory::singleton()->getTitleForId( $entityId );

		if ( $entityTitle === null ) {
			$this->dieUsage( 'No such entity: ' . $entityId, 'entity-not-found' );
		}

		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;

		return $this->loadEntityContent( $entityTitle, $baseRevisionId );
	}

	/**
	 * Checks if the required parameters are set and are valid and consistent.
	 *
	 * @since 0.2
	 */
	protected function checkParameterRequirements() {
		// noop
	}

	/**
	 * @return EntityId
	 */
	protected function getPropertyId() {
		$params = $this->extractRequestParams();

		$libRegistry = new LibRegistry( Settings::singleton() );
		$parseResult = $libRegistry->getEntityIdParser()->parse( $params['property'] );

		if ( !$parseResult->isValid() ) {
			$this->dieUsage( $parseResult->getError()->getText(), 'illegal-property-id' );
		}

		return $parseResult->getValue();
	}

	/**
	 * @since 0.3
	 *
	 * @param Claim $claim
	 */
	protected function outputClaim( Claim $claim ) {
		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();

		$serializer = $serializerFactory->newSerializerForObject( $claim );
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
			'token' => null,
			'baserevid' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'bot' => null,
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
			'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
			'baserevid' => array( 'The numeric identifier for the revision to base the modification on.',
				"This is used for detecting conflicts during save."
			),
			'bot' => array( 'Mark this edit as bot',
				'This URL flag will only be respected if the user belongs to the group "bot".'
			),
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
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#' . $this->getModuleName();
	}

	/**
	 * @see ApiBase::getVersion
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getVersion() {
		return get_class( $this ) . '-' . WB_VERSION;
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
