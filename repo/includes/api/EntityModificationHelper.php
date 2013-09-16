<?php

namespace Wikibase\Api;

use Title;
use ValueParsers\ParseException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityContentFactory;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Test\Api\WikibaseApiTestCase;

/**
 * Helper class for modifying entities
 *
 * @since 0.5
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Adam Shorland
 */
class EntityModificationHelper {

	/**
	 * @since 0.5
	 *
	 * @var EntityIdParser
	 */
	protected $entityIdParser;

	/**
	 * @since 0.5
	 *
	 * @param \ApiMain $apiMain
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct(
		\ApiMain $apiMain,
		EntityIdParser $entityIdParser
	) {
		$this->apiMain = $apiMain;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * Parses an entity id string coming from the user
	 *
	 * @param string $entityIdParam
	 *
	 * @return EntityId
	 */
	public function getEntityIdFromString( $entityIdParam ) {
		try {
			return $this->entityIdParser->parse( $entityIdParam );
		} catch ( ParseException $parseException ) {
			$this->apiMain->dieUsage( 'Invalid entity ID: ParseException', 'invalid-entity-id' );
		}
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Title
	 */
	public function getEntityTitleFromEntityId( EntityId $entityId ) {
		$entityTitle = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getTitleForId( $entityId );

		if ( $entityTitle === null ) {
			$this->apiMain->dieUsage( 'No such entity' , 'no-such-entity' );
		}

		return $entityTitle;
	}

}