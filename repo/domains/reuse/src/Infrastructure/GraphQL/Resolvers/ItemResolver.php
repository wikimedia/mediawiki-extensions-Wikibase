<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\Deferred;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItems;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItemsRequest;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Aliases;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Descriptions;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Item;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Labels;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Sitelinks;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statements;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLError;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\QueryContext;

/**
 * @license GPL-2.0-or-later
 */
class ItemResolver {
	private array $itemsToFetch = [];
	private ?ItemsBatch $itemsBatch = null;

	public function __construct( private readonly BatchGetItems $batchGetItems ) {
	}

	public function resolveItem( string $itemId, QueryContext $context, bool $throwForMissingItems = true ): Deferred {
		$this->itemsToFetch[] = $itemId;

		/**
		 * @throws GraphQLError
		 */
		return new Deferred( function() use (
			$itemId,
			$context,
			$throwForMissingItems
			): Item {
			if ( !$this->itemsBatch ) {
				$this->itemsBatch = $this->batchGetItems
					->execute( new BatchGetItemsRequest( $this->itemsToFetch ) )
					->itemsBatch;
			}

			$item = $this->itemsBatch->getItem( new ItemId( $itemId ) );
			if ( !$item ) {
				if ( $throwForMissingItems ) {
					throw GraphQLError::itemNotFound( $itemId );
				}
				$context->missingItemIds[] = $itemId;
				return $this->createStubItem( $itemId );
			}

			$resultId = $item->id->getSerialization();
			if ( $resultId !== $itemId ) {
				$context->redirects[$itemId] = $resultId;
			}
			return $item;
		} );
	}

	public function resolveItems( array $ids, QueryContext $context, bool $throwForMissingItems = true ): array {
		return array_map(
			fn( $id ) => $this->resolveItem( $id, $context, $throwForMissingItems ),
			$ids
		);
	}

	private function createStubItem( string $itemId ): Item {
		return new Item(
			new ItemId( $itemId ),
			new Labels(),
			new Descriptions(),
			new Aliases(),
			new Sitelinks(),
			new Statements()
		);
	}
}
