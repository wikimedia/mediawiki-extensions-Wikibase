<?php

namespace Wikibase\Api;

use ApiBase, MWException;

use DataValues\IllegalValueException;
use ApiMain;
use Wikibase\EntityId;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\Statement;
use Wikibase\Reference;
use Wikibase\Snaks;
use Wikibase\SnakList;
use Wikibase\Claims;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * API module for creating a reference or setting the value of an existing one.
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
class SetReference extends ApiWikibase {

	// TODO: auto comment
	// TODO: rights
	// TODO: conflict detection

	/**
	 * @var SnakValidationHelper
	 */
	protected $snakValidation;

	/**
	 * see ApiBase::__construct()
	 *
	 * @param ApiMain $mainModule
	 * @param string  $moduleName
	 * @param string  $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$this->snakValidation = new SnakValidationHelper(
			$this,
			WikibaseRepo::getDefaultInstance()->getPropertyDataTypeLookup(),
			WikibaseRepo::getDefaultInstance()->getDataTypeFactory(),
			new ValidatorErrorLocalizer()
		);
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$content = $this->getEntityContent();
		$params = $this->extractRequestParams();

		$reference = $this->updateReference(
			$content->getEntity(),
			$params['statement'],
			$this->getSnaks( $params['snaks'] ),
			$params['reference']
		);

		$this->saveChanges( $content );

		$this->outputReference( $reference );

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

		if ( $entityId === null ) {
			$this->dieUsage( 'Could not find an existing entity' , 'no-such-entity' );
		}

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
	 * @param string $rawSnaks
	 *
	 * @return \Wikibase\Snaks
	 */
	protected function getSnaks( $rawSnaks ) {
		$rawSnaks = \FormatJson::decode( $rawSnaks, true );

		if ( !is_array( $rawSnaks ) || !count( $rawSnaks ) ) {
			$this->dieUsage( 'No snaks or invalid JSON given', 'invalid-json' );
		}

		$snaks = new SnakList();

		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$snakUnserializer = $serializerFactory->newUnserializerForClass( 'Wikibase\Snak' );

		try {
			foreach ( $rawSnaks as $byPropertySnaks ) {
				if ( !is_array( $byPropertySnaks ) ) {
					$this->dieUsage( 'Invalid snak JSON given', 'invalid-json' );
				}
				foreach ( $byPropertySnaks as $rawSnak ) {
					$snak = $snakUnserializer->newFromSerialization( $rawSnak );
					$this->snakValidation->validateSnak( $snak );
					$snaks[] = $snak;
				}
			}
		} catch ( IllegalValueException $ex ) {
			// Handle Snak instantiation failures
			$this->dieUsage( 'Invalid snak JSON given. IllegalValueException', 'invalid-json' );
		}

		return $snaks;
	}

	/**
	 * @since 0.3
	 *
	 * @param \Wikibase\Entity $entity
	 * @param string $statementGuid
	 * @param \Wikibase\Snaks $snaks
	 * @param string|null $refHash
	 *
	 * @return \Wikibase\Reference
	 */
	protected function updateReference( Entity $entity, $statementGuid, Snaks $snaks, $refHash = null ) {
		$claims = new Claims( $entity->getClaims() );

		if ( !$claims->hasClaimWithGuid( $statementGuid ) ) {
			$this->dieUsage( 'Could not find the statement' , 'no-such-statement' );
		}

		$statement = $claims->getClaimWithGuid( $statementGuid );

		if ( ! ( $statement instanceof Statement ) ) {
			$this->dieUsage( 'The referenced claim is not a statement and thus cannot have references', 'not-statement' );
		}

		$reference = new Reference( $snaks );

		/**
		 * @var \Wikibase\References $references
		 */
		$references = $statement->getReferences();

		if ( $refHash !== null ) {
			if ( $references->hasReferenceHash( $refHash ) ) {
				$references->removeReferenceHash( $refHash );
			}
			else {
				$this->dieUsage( 'The statement does not have any associated reference with the provided reference hash', 'no-such-reference' );
			}
		}

		// Only adding the reference if there is none with the same hash yet.
		// TODO: verify this is what we want to do
		if ( !$references->hasReference( $reference ) ) {
			$references->addReference( $reference );
		}

		$entity->setClaims( $claims );

		return $reference;
	}

	/**
	 * @since 0.3
	 *
	 * @param \Wikibase\EntityContent $content
	 */
	protected function saveChanges( EntityContent $content ) {
		$summary = '/* wbsetreference */'; // TODO: automcomment
		$status = $this->attemptSaveEntity( $content,
			$summary,
			EDIT_UPDATE );

		$this->addRevisionIdFromStatusToResult( 'pageinfo', 'lastrevid', $status );
	}

	/**
	 * @since 0.3
	 *
	 * @param \Wikibase\Reference $reference
	 */
	protected function outputReference( Reference $reference ) {
		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $reference );
		$serializer->getOptions()->setIndexTags( $this->getResult()->getIsRawMode() );

		$this->getResult()->addValue(
			null,
			'reference',
			$serializer->getSerialized( $reference )
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
			'snaks' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'reference' => array(
				ApiBase::PARAM_TYPE => 'string',
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
			array( 'code' => 'invalid-json', 'info' => $this->msg( 'wikibase-api-invalid-json' )->text() ),
			array( 'code' => 'no-such-statement', 'info' => $this->msg( 'wikibase-api-no-such-statement' )->text() ),
			array( 'code' => 'not-statement', 'info' => $this->msg( 'wikibase-api-not-statement' )->text() ),
			array( 'code' => 'no-such-reference', 'info' => $this->msg( 'wikibase-api-no-such-reference' )->text() ),
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
			'statement' => 'A GUID identifying the statement for which a reference is being set',
			'snaks' => 'The snaks to set the reference to. JSON object with property ids pointing to arrays containing the snaks for that property',
			'reference' => 'A hash of the reference that should be updated. Optional. When not provided, a new reference is created',
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
			'API module for creating a reference or setting the value of an existing one.'
		);
	}

	/**
	 * @see \ApiBase::getExamples()
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetreference&statement=q76$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF&snaks={"p39":[{"snaktype":"value","property":"p14","datavalue":{"type":"string","value":"wikipedia"}}}&baserevid=7201010&token=foobar' => 'Create a new reference for claim with GUID q76$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF',
			'api.php?action=wbsetreference&statement=q76$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF&reference=1eb8793c002b1d9820c833d234a1b54c8e94187e&snaks={"p39":[{"snaktype":"value","property":"p14","datavalue":{"type":"string","value":"wikipedia"}}}&baserevid=7201010&token=foobar' => 'Set reference for claim with GUID q76$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF which has hash of 1eb8793c002b1d9820c833d234a1b54c8e94187e',
		);
	}

	/**
	 * @see \ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
	}

}
