<?php

declare( strict_types=1 );

namespace Wikibase\Client\Store\Sql;

use PageProps;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityIdLookup;

/**
 * Lookup of EntityIds based on wikibase_item entries in the page_props table.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class PagePropsEntityIdLookup implements EntityIdLookup {

	/**
	 * @var PageProps
	 */
	private $pageProps;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	public function __construct(
		PageProps $pageProps,
		EntityIdParser $idParser
	) {
		$this->pageProps = $pageProps;
		$this->idParser = $idParser;
	}

	/**
	 * @see EntityIdLookup::getEntityIds
	 *
	 * @param Title[] $titles
	 *
	 * @return EntityId[]
	 */
	public function getEntityIds( array $titles ): array {
		$pages = array_filter( $titles, function( Title $title ): bool {
			return $title->canExist();
		} );
		return array_map( [ $this->idParser, 'parse' ],
			$this->pageProps->getProperties( $pages, 'wikibase_item' ) );
	}

	/**
	 * @see EntityIdLookup::getEntityIdForTitle
	 */
	public function getEntityIdForTitle( Title $title ): ?EntityId {
		$entityIds = $this->getEntityIds( [ $title ] );

		return reset( $entityIds ) ?: null;
	}

}
