<?php

namespace Wikibase\Api;

use ApiBase, MWException;

use Wikibase\EntityId;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\Statement;
use Wikibase\Reference;
use Wikibase\Snaks;
use Wikibase\SnakList;
use Wikibase\Claims;
use Wikibase\Settings;

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

	// TODO: automcomment
	// TODO: example
	// TODO: rights
	// TODO: conflict detection

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

		$entityId = EntityId::newFromPrefixedId( Entity::getIdFromClaimGuid( $params['statement'] ) );

		if ( $entityId === null ) {
			$this->dieUsage( 'No such entity', 'setreference-entity-not-found' );
		}

		$entityTitle = EntityContentFactory::singleton()->getTitleForId( $entityId );

		if ( $entityTitle === null ) {
			$this->dieUsage( 'No such entity', 'setreference-entity-not-found' );
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

		$snaks = new SnakList();

		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$snakUnserializer = $serializerFactory->newUnserializerForClass( 'Wikibase\Snak' );

		foreach ( $rawSnaks as $byPropertySnaks ) {
			foreach ( $byPropertySnaks as $rawSnak ) {
				$snaks[] = $snakUnserializer->newFromSerialization( $rawSnak );
			}
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
			$this->dieUsage( 'No such statement', 'setreference-statement-not-found' );
		}

		$statement = $claims->getClaimWithGuid( $statementGuid );

		if ( ! ( $statement instanceof Statement ) ) {
			$this->dieUsage(
				'The referenced claim is not a statement and thus cannot have references',
				'setreference-not-a-statement'
			);
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
				$this->dieUsage(
					'The statement does not have any associated reference with the provided reference hash',
					'setreference-no-such-reference'
				);
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
			'bot' => null,
		);
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
	 * @see \ApiBase::getExamples
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?statement=q586$57CE3C9F-37AF-42B5-B067-DADA198DD579&snaks={"p1":[{snak}, {snak}], "p2": [{snak}]}&token=foo&baserevid=42' =>
				'Creating a new reference with 3 snaks',
			'api.php?statement=q586$57CE3C9F-37AF-42B5-B067-DADA198DD579&snaks={"p2": [{snak}]}&reference=da39a3ee5e6b4b0d3255bfef95601890afd80709&token=foo&baserevid=42' =>
				'Updating an existing reference to contain a single snak',
		);
	}

	/**
	 * @see \ApiBase::getHelpUrls
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetreference';
	}

	/**
	 * @see \ApiBase::getVersion
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
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
