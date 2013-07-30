<?php
namespace Wikibase\Api;

use ApiBase, MWException;
use Wikibase\EntityContent;
use Wikibase\Claim;
use Wikibase\Summary;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\EntityContentFactory;

/**
 * Base class for modifying claims, with common functionality
 * for creating summaries.
 *
 * @todo decide if this is really needed or not
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
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class ModifyClaim extends ApiWikibase {

	/**
	 * @since 0.4
	 *
	 * @param \Wikibase\EntityContent $content
	 * @param \Wikibase\Summary $summary
	 */
	protected function saveChanges( EntityContent $content, Summary $summary ) {
		$status = $this->attemptSaveEntity(
			$content,
			$summary->toString(),
			EDIT_UPDATE
		);

		$this->addRevisionIdFromStatusToResult( 'pageinfo', 'lastrevid', $status );
	}

	/**
	 * @since 0.4
	 *
	 * @param Claim $claim
	 */
	protected function outputClaim( Claim $claim ) {
		$serializerFactory = new SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $claim );
		$serializer->getOptions()->setIndexTags( $this->getResult()->getIsRawMode() );

		$this->getResult()->addValue(
			null,
			'claim',
			$serializer->getSerialized( $claim )
		);
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @return \Wikibase\EntityContent
	 */
	protected function getEntityContent( EntityId $entityId ) {
		$params = $this->extractRequestParams();
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$entityTitle = EntityContentFactory::singleton()->getTitleForId( $entityId );

		if ( $entityTitle === null ) {
			$this->dieUsage( 'No such entity' , 'no-such-entity' );
		}

		return $this->loadEntityContent( $entityTitle, $baseRevisionId );
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 * @param EntityId $propertyId
	 *
	 * @return \Wikibase\Snak
	 *
	 * @throws ParseException
	 * @throws IllegalValueException
	 */
	protected function getSnakInstance( $params, EntityId $propertyId ) {
		$valueData = null;
		if ( isset( $params['value'] ) ) {
			$valueData = \FormatJson::decode( $params['value'], true );
			if ( $valueData === null ) {
				$this->dieUsage( 'Could not decode snak value', 'invalid-snak' );
			}
		}

		$factory = WikibaseRepo::getDefaultInstance()->getSnakConstructionService();

		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			$this->dieUsage( 'Property expected, got ' . $propertyId->getEntityType(), 'invalid-snak' );
		}

		try {
			$snak = $factory->newSnak( $propertyId, $params['snaktype'], $valueData );
		}
		catch ( IllegalValueException $ex ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Invalid snak: IllegalValueException', 'invalid-snak' );
		}
		catch ( InvalidArgumentException $ex ) {
			// shouldn't happen, but might.
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Invalid snak: InvalidArgumentException', 'invalid-snak' );
		}

		return $snak;
	}

	/**
	 * Create a new Summary instance suitable for representing the action performed by this module.
	 *
	 * @since 0.4
	 *
	 * @param array $params
	 *
	 * @return Summary
	 */
	protected function createSummary( array $params ) {
		$summary = new Summary( $this->getModuleName() );
		if ( isset( $params['summary'] ) ) {
			$summary->setUserSummary( $params['summary'] );
		}
		return $summary;
	}

	/**
	 * @see ApiBase::isWriteMode
	 * @return bool true
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see  \Api::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );

		$permissions[] = 'edit';
		return $permissions;
	}

	/**
	 * @see \ApiBase::getAllowedParams
	 */
	public function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			array( 'summary' => array( ApiBase::PARAM_TYPE => 'string' ) )
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge(
			parent::getPossibleErrors(),
			array(
				array( 'code' => 'invalid-guid', 'info' => $this->msg( 'wikibase-api-invalid-guid' )->text() ),
				array( 'code' => 'no-such-entity', 'info' => $this->msg( 'wikibase-api-no-such-entity' )->text() ),
				array( 'code' => 'invalid-snak', 'info' => $this->msg( 'wikibase-api-invalid-snak' )->text() ),
			)
		);
	}

	/**
	 * @see ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			array( 'summary' => array( 'Summary for the edit.',
				"Will be prepended by an automatically generated comment. The length limit of the
				autocomment together with the summary is 260 characters. Be aware that everything above that
				limit will be cut off." )
			)
		);
	}
}
