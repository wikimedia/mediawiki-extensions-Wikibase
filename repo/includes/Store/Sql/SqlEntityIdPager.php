<?php

namespace Wikibase\Repo\Store\Sql;

use InvalidArgumentException;
use ResultWrapper;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Store\EntityIdPager;
use Wikimedia\Assert\Assert;

/**
 * SqlEntityIdPager is a cursor for iterating over the EntityIds stored in
 * the current Wikibase installation.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class SqlEntityIdPager implements EntityIdPager {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var string|null
	 */
	private $entityType;

	/**
	 * @var string
	 */
	private $redirectMode;

	/**
	 * Last page_id selected.
	 *
	 * @var int
	 */
	private $position = 0;

	/**
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param EntityIdParser $entityIdParser
	 * @param null|string $entityType The desired entity type, or null for any type.
	 * @param string $redirectMode A EntityIdPager::XXX_REDIRECTS constant (default is NO_REDIRECTS).
	 */
	public function __construct(
		EntityNamespaceLookup $entityNamespaceLookup,
		EntityIdParser $entityIdParser,
		$entityType = null,
		$redirectMode = EntityIdPager::NO_REDIRECTS
	) {
		Assert::parameterType( 'string|null', $entityType, '$entityType' );

		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->entityIdParser = $entityIdParser;
		$this->entityType = $entityType;
		$this->redirectMode = $redirectMode;
	}

	/**
	 * Fetches the next batch of IDs. Calling this has the side effect of advancing the
	 * internal state of the page, typically implemented by some underlying resource
	 * such as a file pointer or a database connection.
	 *
	 * @note: After some finite number of calls, this method should eventually return
	 * an empty list of IDs, indicating that no more IDs are available.
	 *
	 * @since 0.5
	 *
	 * @param int $limit The maximum number of IDs to return.
	 *
	 * @return EntityId[] A list of EntityIds matching the given parameters. Will
	 * be empty if there are no more entities to list from the given offset.
	 */
	public function fetchIds( $limit ) {
		Assert::parameter( is_int( $limit ) && $limit > 0, '$limit', '$limit must be a positive integer' );

		$dbr = wfGetDB( DB_REPLICA );
		$rows = $dbr->select(
			'page',
			[ 'page_id', 'page_title' ],
			$this->getWhere(),
			__METHOD__,
			[
				'ORDER BY' => 'page_id ASC',
				'LIMIT' => $limit
			]
		);

		list( $entityIds, $position ) = $this->processRows( $rows );
		if ( $position !== null ) {
			$this->position = $position;
		}

		return $entityIds;
	}

	/**
	 * @return array
	 */
	private function getWhere() {
		$where = [ 'page_id > ' . (int)$this->position ];

		if ( $this->entityType === null ) {
			$where['page_namespace'] = $this->entityNamespaceLookup->getEntityNamespaces();
		} else {
			$where['page_namespace'] = $this->entityNamespaceLookup->getEntityNamespace(
				$this->entityType
			);
		}

		if ( $this->redirectMode === self::NO_REDIRECTS ) {
			$where['page_is_redirect'] = 0;
		} elseif ( $this->redirectMode === self::ONLY_REDIRECTS ) {
			$where['page_is_redirect'] = 1;
		}

		return $where;
	}

	/**
	 * Processes the query result: Parse the EntityIds and compute the next
	 * position. Returns an array with said entity ids and the next position
	 * or null in case the position didn't change.
	 *
	 * @param ResultWrapper $rows
	 *
	 * @return array Tuple with ( EntityId[], int|null )
	 */
	private function processRows( $rows ) {
		$entityIds = [];
		$position = null;

		foreach ( $rows as $row ) {
			try {
				$this->position = (int)$row->page_id;
				$entityIds[] = $this->entityIdParser->parse( $row->page_title );
			} catch ( EntityIdParsingException $ex ) {
				wfLogWarning( 'Unexpected entity id serialization "' . $row->page_title . '"' );
			}
		}

		return [ $entityIds, $position ];
	}

}
