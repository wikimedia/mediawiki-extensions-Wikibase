<?php

namespace Wikibase\Api;

use ApiBase, MWException;
use Wikibase\EntityContent;
use Wikibase\Claim;
use Wikibase\Summary;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\EntityTitleLookup;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Lib\SnakConstructionService;
use IllegalValueException;
use InvalidArgumentException;

/**
 * Helper class for modifying claims
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
class ClaimModificationHelper {

	/**
	 * @since 0.4
	 *
	 * @var \ApiMain
	 */
	protected $apiMain;

	/**
	 * @since 0.4
	 *
	 * @var EntityTitleLookup
	 */
	protected $entityTitelLookup;

	/**
	 * @since 0.4
	 *
	 * @var SnakConstructionService
	 */
	protected $snakConstructionService;

	/**
	 * @since 0.4
	 *
	 * @var EntityIdParser
	 */
	protected $entityIdParser;

	/**
	 * @since 0.4
	 *
	 * @param \ApiMain $apiMain
	 * @param EntityTitleLookup $entityTitelLookup
	 * @param SnakConstructionService $snakConstructionService
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct(
		\ApiMain $apiMain,
		EntityTitleLookup $entityTitelLookup,
		SnakConstructionService $snakConstructionService,
		EntityIdParser $entityIdParser
	) {
		$this->apiMain = $apiMain;
		$this->entityTitelLookup = $entityTitelLookup;
		$this->snakConstructionService = $snakConstructionService;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @since 0.4
	 *
	 * @param Claim $claim
	 */
	public function addClaimToApiResult( Claim $claim ) {
		$serializerFactory = new SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $claim );
		$serializer->getOptions()->setIndexTags( $this->apiMain->getResult()->getIsRawMode() );

		$this->apiMain->getResult()->addValue(
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
	 * @return \Title
	 * TODO: this could go into a ApiWikibaseHelper as it is useful for almost all API modules
	 */
	public function getEntityTitle( EntityId $entityId ) {
		$entityTitle = $this->entityTitelLookup->getTitleForId( $entityId );

		if ( $entityTitle === null ) {
			$this->apiMain->dieUsage( 'No such entity' , 'no-such-entity' );
		}

		return $entityTitle;
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
	public function getSnakInstance( $params, EntityId $propertyId ) {
		$valueData = null;
		if ( isset( $params['value'] ) ) {
			$valueData = \FormatJson::decode( $params['value'], true );
			if ( $valueData === null ) {
				$this->apiMain->dieUsage( 'Could not decode snak value', 'invalid-snak' );
			}
		}

		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			$this->apiMain->dieUsage( 'Property expected, got ' . $propertyId->getEntityType(), 'invalid-snak' );
		}

		try {
			$snak = $this->snakConstructionService->newSnak( $propertyId, $params['snaktype'], $valueData );
		}
		catch ( IllegalValueException $ex ) {
			$this->apiMain->dieUsage( 'Invalid snak: IllegalValueException', 'invalid-snak' );
		}
		catch ( InvalidArgumentException $ex ) {
			// shouldn't happen, but might.
			$this->apiMain->dieUsage( 'Invalid snak: InvalidArgumentException', 'invalid-snak' );
		}

		return $snak;
	}

	/**
	 * Parses an entity id string coming from the user
	 *
	 * @since 0.4
	 *
	 * @param string $entityIdParam
	 *
	 * TODO: this could go into an EntityModificationHelper or even in a ApiWikibaseHelper
	 * as it is useful for almost all API modules
	 */
	public function getEntityIdFromString( $entityIdParam ) {
		try {
			$entityId = $this->entityIdParser->parse( $entityIdParam );
		} catch ( ParseException $parseException ) {
			$this->dieUsage( 'Invalid entity ID: ParseException', 'invalid-entity-id' );
		}

		return $entityId;
	}

	/**
	 * Creates a new Summary instance suitable for representing the action performed by this module.
	 *
	 * @since 0.4
	 *
	 * @param array $params
	 *
	 * @return Summary
	 */
	public function createSummary( array $params, \ApiBase $module ) {
		$summary = new Summary( $module->getModuleName() );
		if ( isset( $params['summary'] ) ) {
			$summary->setUserSummary( $params['summary'] );
		}
		return $summary;
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array(
			array( 'code' => 'invalid-guid', 'info' => $this->apiMain->msg( 'wikibase-api-invalid-guid' )->text() ),
			array( 'code' => 'no-such-entity', 'info' => $this->apiMain->msg( 'wikibase-api-no-such-entity' )->text() ),
			array( 'code' => 'invalid-snak', 'info' => $this->apiMain->msg( 'wikibase-api-invalid-snak' )->text() ),
			array( 'code' => 'invalid-entity-id', 'info' => $this->apiMain->msg( 'wikibase-api-invalid-entity-id' )->text() ),
		);
	}
}
