<?php

namespace Wikibase\Api;

use ApiBase;
use MWException;

use Wikibase\EntityId;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\Statement;
use Wikibase\Settings;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for setting the rank of a statement
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
 * @since 0.3
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SetStatementRank extends ApiWikibase {

	// TODO: automcomment
	// TODO: example
	// TODO: rights
	// TODO: conflict detection

	public function __construct( $mainModule, $moduleName, $modulePrefix = '' ) {
		//NOTE: need to declare this constructor, so old PHP versions don't use the
		//setStatementRank() function as the constructor.
		parent::__construct( $mainModule, $moduleName, $modulePrefix );
	}

	/**
	 * @see \ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$content = $this->getEntityContent();
		$params = $this->extractRequestParams();

		$statement = $this->setStatementRank(
			$content->getEntity(),
			$params['statement'],
			$params['rank']
		);

		$this->saveChanges( $content );

		$this->outputStatement( $statement );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @since 0.3
	 *
	 * @return \Wikibase\EntityContent
	 */
	protected function getEntityContent() {
		$params = $this->extractRequestParams();

		// @todo generalize handling of settings in api modules
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$entityPrefixes = $settings->getSetting( 'entityPrefixes' );
		$claimGuidValidator = new ClaimGuidValidator( $entityPrefixes );

		if ( !( $claimGuidValidator->validate( $params['statement'] ) ) ) {
			$this->dieUsage( 'Invalid claim guid' , 'invalid-guid' );
		}

		$entityId = EntityId::newFromPrefixedId( Entity::getIdFromClaimGuid( $params['statement'] ) );
		$entityTitle = EntityContentFactory::singleton()->getTitleForId( $entityId );

		if ( $entityTitle === null ) {
			$this->dieUsage( 'Could not find an existing entity' , 'no-such-entity' );
		}

		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;

		return $this->loadEntityContent( $entityTitle, $baseRevisionId );
	}

	/**
	 * @since 0.3
	 *
	 * @param Entity $entity
	 * @param string $statementGuid
	 * @param string $rank
	 *
	 * @return \Wikibase\Statement
	 */
	protected function setStatementRank( Entity $entity, $statementGuid, $rank ) {
		$claims = new \Wikibase\Claims( $entity->getClaims() );

		if ( !$claims->hasClaimWithGuid( $statementGuid ) ) {
			$this->dieUsage( 'Could not find the statement' , 'no-such-statement' );
		}

		$statement = $claims->getClaimWithGuid( $statementGuid );

		if ( ! ( $statement instanceof Statement ) ) {
			$this->dieUsage( 'The referenced claim is not a statement and thus does not have a rank' , 'not-statement' );
		}

		$statement->setRank( ClaimSerializer::unserializeRank( $rank ) );

		$entity->setClaims( $claims );

		return $statement;
	}

	/**
	 * @since 0.3
	 *
	 * @param \Wikibase\EntityContent $content
	 */
	protected function saveChanges( EntityContent $content ) {
		// collect information and create an EditEntity
		$summary = '/* wbsetstatementrank */'; // TODO: automcomment
		$status = $this->attemptSaveEntity( $content,
			$summary,
			EDIT_UPDATE );

		$this->addRevisionIdFromStatusToResult( 'pageinfo', 'lastrevid', $status );
	}

	/**
	 * @since 0.3
	 *
	 * @param \Wikibase\Statement $statement
	 */
	protected function outputStatement( Statement $statement ) {
		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $statement );

		$serializer->getOptions()->setIndexTags( $this->getResult()->getIsRawMode() );

		$this->getResult()->addValue(
			null,
			'statement',
			$serializer->getSerialized( $statement )
		);
	}

	/**
	 * @see \ApiBase::getAllowedParams
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'statement' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'rank' => array(
				ApiBase::PARAM_TYPE => ClaimSerializer::getRanks(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'token' => null,
			'baserevid' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'bot' => false,
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'invalid-guid', 'info' => $this->msg( 'wikibase-api-invalid-guid' )->text() ),
			array( 'code' => 'no-such-entity', 'info' => $this->msg( 'wikibase-api-no-such-entity' )->text() ),
			array( 'code' => 'no-such-statement', 'info' => $this->msg( 'wikibase-api-no-such-statement' )->text() ),
			array( 'code' => 'not-statement', 'info' => $this->msg( 'wikibase-api-not-statement' )->text() ),
		) );
	}

	/**
	 * @see \ApiBase::getParamDescription
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'statement' => 'A GUID identifying the statement for which to set the rank',
			'rank' => 'The new value to set for the rank',
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
	 * @see \ApiBase::getDescription
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module for setting the rank of a Wikibase statement.'
		);
	}

	/**
	 * @see \ApiBase::getExamples
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			// TODO
			// 'ex' => 'desc'
		);
	}

	/**
	 * @see \ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
	}

}
