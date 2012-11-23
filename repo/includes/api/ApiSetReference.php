<?php

namespace Wikibase;
use ApiBase, MWException;

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
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiSetReference extends Api {

	// TODO: high level tests
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
		wfProfileIn( "Wikibase-" . __METHOD__ );

		$content = $this->getEntityContent();
		$params = $this->extractRequestParams();

		$reference = $this->updateReference(
			$content->getEntity(),
			$this->getSnaks( $params['snaks'] ),
			$params['reference']
		);

		$status = $content->save();

		if ( !$status->isGood() ) {
			$this->dieUsage( 'Failed to save the change', 'setclaimvalue-save-failed' );
		}

		$this->outputReference( $reference );

		wfProfileOut( "Wikibase-" . __METHOD__ );
	}

	/**
	 * @since 0.3
	 *
	 * @return EntityContent
	 */
	protected function getEntityContent() {
		$params = $this->extractRequestParams();

		$entityId = EntityId::newFromPrefixedId( EntityObject::getIdFromClaimGuid( $params['statement'] ) );
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
	 * @return Snaks
	 */
	protected function getSnaks( $rawSnaks ) {
		$rawSnaks = \FormatJson::decode( $rawSnaks, true );

		$snaks = new SnakList();
		$snakUnserializer = new SnakSerializer();

		foreach ( $rawSnaks as $rawSnak ) {
			$snaks[] = $snakUnserializer->getUnserialized( $rawSnak );
		}

		return $snaks;
	}

	/**
	 * @since 0.3
	 *
	 * @param Entity $entity
	 * @param string $statementGuid
	 * @param Snaks $snaks
	 * @param string|null $refHash
	 *
	 * @return Reference
	 */
	protected function updateReference( Entity $entity, $statementGuid, Snaks $snaks, $refHash = null ) {
		if ( !$entity->getClaims()->hasClaimWithGuid( $statementGuid ) ) {
			$this->dieUsage( 'No such statement', 'setreference-statement-not-found' );
		}

		$statement = $entity->getClaims()->getClaimWithGuid( $statementGuid );

		if ( ! ( $statement instanceof Statement ) ) {
			$this->dieUsage( 'The referenced claim is not a statement and thus cannot have references', 'setreference-not-a-statement' );
		}

		$reference = new ReferenceObject( $snaks );

		/**
		 * @var References $references
		 */
		$references = $statement->getReferences();

		$references->addReference( $reference );

		// TODO: either remove any duplicate before, update the existing one, or remove duplicates after

		return $reference;
	}

	/**
	 * @since 0.3
	 *
	 * @param Reference $reference
	 */
	protected function outputReference( Reference $reference ) {
		$snakSerializer = new SnakSerializer();
		$snakSerializer->getOptions()->setIndexTags( $this->getResult()->getIsRawMode() );

		$snaksSerializer = new ByPropertyListSerializer( 'reference', $snakSerializer );
		$snaksSerializer->getOptions()->setIndexTags( $this->getResult()->getIsRawMode() );

		$this->getResult()->addValue(
			null,
			'claim',
			$snaksSerializer->getSerialized( $reference->getSnaks() )
		);
	}

	/**
	 * @see ApiBase::getAllowedParams
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
		);
	}

	/**
	 * @see ApiBase::getParamDescription
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'statement' => 'A GUID identifying the statement for which a reference is being set',
			'snaks' => 'The snaks to set the reference to',
			'reference' => 'A hash of the reference that should be updated. Optional. When not provided, a new reference is created',
			'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
			'baserevid' => array( 'The numeric identifier for the revision to base the modification on.',
				"This is used for detecting conflicts during save."
			),
		);
	}

	/**
	 * @see ApiBase::getDescription
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module for setting the value of a Wikibase claim.'
		);
	}

	/**
	 * @see ApiBase::getExamples
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
	 * @see ApiBase::getHelpUrls
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetclaimvalue';
	}

	/**
	 * @see ApiBase::getVersion
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
	}

}
