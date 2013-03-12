<?php

namespace Wikibase\Api;

use MWException;
use ApiBase;

use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\Claim;
use Wikibase\EntityId;
use Wikibase\EntityContentFactory;

/**
 * API module for creating or updating an entire Claim.
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
 */
class SetClaim extends ApiWikibase {

	// TODO: rights

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.4
	 */
	public function execute() {
		$claim = $this->getClaimFromRequest();

		$claimSetter = new ClaimSetter();

		$params = $this->extractRequestParams();
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$token = isset( $params['token'] ) ? $params['token'] : '';

		$newRevisionId = null;

		try {
			$newRevisionId = $claimSetter->setClaim( $claim, $baseRevisionId, $token, $this->getUser() );
		}
		catch ( ExceptionWithCode $exception ) {
			$this->dieUsage( $exception->getMessage(), $exception->getErrorCode() );
		}

		if ( $newRevisionId !== null ) {
			$this->getResult()->addValue(
				'pageinfo',
				'lastrevid',
				$newRevisionId
			);
		}

		$this->outputClaim( $claim );
	}

	/**
	 * @since 0.4
	 *
	 * @return Claim
	 */
	protected function getClaimFromRequest() {
		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$unserializer = $serializerFactory->newUnserializerForClass( 'Wikibase\Claim' );

		$params = $this->extractRequestParams();
		$claim = $unserializer->newFromSerialization( \FormatJson::decode( $params['claim'], true ) );

		assert( $claim instanceof Claim );

		return $claim;
	}

	/**
	 * @since 0.4
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
	 * @since 0.4
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'claim' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
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
	 * @since 0.4
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'claim' => 'Claim serialization',
			'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
			'baserevid' => array( 'The numeric identifier for the revision to base the modification on.',
				"This is used for detecting conflicts during save."
			),
		);
	}

	/**
	 * @see ApiBase::getDescription
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module for creating or updating an entire Claim.'
		);
	}

	/**
	 * @see ApiBase::getExamples
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=setclaim&claim={json-stuff}&baserevid=9042&token=foobar'
			// 'ex' => 'desc'
		);
	}

	/**
	 * @see ApiBase::getHelpUrls
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetclaimvalue';
	}

	/**
	 * @see ApiBase::getVersion
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
	}

}

/**
 * Exception with a string error code.
 *
 * TODO: to own file
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
 */
class ExceptionWithCode extends \Exception {

	/**
	 * @var string
	 */
	private $stringCode;

	/**
	 * @param string $message
	 * @param string $code
	 */
	public function __construct( $message, $code ) {
		parent::__construct( $message );
		$this->stringCode = $code;
	}

	/**
	 * @return string
	 */
	public function getErrorCode() {
		return $this->stringCode;
	}

}

use User;

/**
 * Class for updating a claim in the primary storage.
 *
 * TODO: to own file
 * FIXME: entity content fetching pulls in global factory
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
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimSetter {

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.4
	 *
	 * @param Claim $claim
	 * @param int|null $baseRevId
	 * @param string $token
	 * @param User $user
	 *
	 * @return int
	 */
	public function setClaim( Claim $claim, $baseRevId, $token, User $user ) {
		$entityId = $this->getEntityIdForClaim( $claim );

		$content = $this->getEntityContent( $entityId, $baseRevId );

		$this->updateClaim( $content->getEntity(), $claim );

		$newRevisionId = $this->saveChanges( $content, $baseRevId, $token, $user );

		return $newRevisionId;
	}

	/**
	 * @param Claim $claim
	 *
	 * @return EntityId
	 * @throws ExceptionWithCode
	 */
	protected function getEntityIdForClaim( Claim $claim ) {
		$guid = $claim->getGuid();

		if ( $guid === null ) {
			throw new ExceptionWithCode( 'The ID of the claim needs to be set', 'setclaim-no-guid' );
		}

		try {
			$entityId = Entity::getIdFromClaimGuid( $guid );
		}
		catch ( MWException $exception ) {
			throw new ExceptionWithCode( $exception->getMessage(), 'setclaim-invalid-guid' );
		}

		$libRegistry = new \Wikibase\LibRegistry( \Wikibase\Settings::singleton() );
		$idParser = $libRegistry->getEntityIdParser();

		$parseResult = $idParser->parse( $entityId );

		if ( $parseResult->isValid() ) {
			$entityId = $parseResult->getValue();
			assert( $entityId instanceof EntityId );
			return $entityId;
		}

		throw new ExceptionWithCode( $parseResult->getError()->getText(), 'setclaim-invalid-guid' );
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param Claim $claim
	 */
	protected function updateClaim( Entity $entity, Claim $claim ) {
		$claims = new \Wikibase\Claims( $entity->getClaims() );

		if ( $claims->hasClaimWithGuid( $claim->getGuid() ) ) {
			$claims->removeClaimWithGuid( $claim->getGuid() );
		}

		$claims->addClaim( $claim );

		$entity->setClaims( $claims );
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 * @param int|null $revisionId
	 *
	 * @return EntityContent
	 * @throws ExceptionWithCode
	 */
	protected function getEntityContent( EntityId $entityId, $revisionId ) {
		if ( $revisionId === null ) {
			$content = EntityContentFactory::singleton()->getFromId( $entityId );
		}
		else {
			$content = EntityContentFactory::singleton()->getFromRevision( $revisionId );
		}

		if ( $content === null ) {
			throw new ExceptionWithCode( 'No such entity', 'setclaim-entity-not-found' );
		}

		if ( !$content->getEntity()->getId()->equals( $entityId ) ) {
			throw new ExceptionWithCode(
				'The provided revision belongs to the wrong entity',
				'setclaim-revision-wrong-entity'
			);
		}

		return $content;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityContent $content
	 * @param int|null $baseRevisionId
	 * @param string $token
	 * @param User $user
	 *
	 * @return int
	 * @throws ExceptionWithCode
	 */
	protected function saveChanges( EntityContent $content, $baseRevisionId, $token, User $user ) {
		$baseRevisionId = is_int( $baseRevisionId ) && $baseRevisionId > 0 ? $baseRevisionId : false;
		$editEntity = new \Wikibase\EditEntity( $content, $user, $baseRevisionId );

		$status = $editEntity->attemptSave(
			'', // TODO: automcomment
			EDIT_UPDATE,
			$token
		);

		if ( !$status->isGood() ) {
			throw new ExceptionWithCode( $status->getMessage(), 'setclaim-save-failed' );
		}

		$statusValue = $status->getValue();
		return (int)$statusValue['revision']->getId();
	}

}