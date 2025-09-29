<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Aliases;
use Wikibase\Repo\Domains\Reuse\Domain\Model\AliasesInLanguage;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Description;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Descriptions;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Item;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Label;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Labels;
use Wikibase\Repo\Domains\Reuse\Domain\Services\ItemsBatchRetriever;

/**
 * @license GPL-2.0-or-later
 */
class EntityLookupItemsBatchRetriever implements ItemsBatchRetriever {

	public function __construct( private readonly EntityLookup $entityLookup ) {
	}

	/**
	 * This implementation just gets items from an EntityLookup one by one. There is room for optimization here.
	 */
	public function getItems( ItemId ...$ids ): ItemsBatch {
		$batch = [];
		foreach ( $ids as $id ) {
			$batch[$id->getSerialization()] = $this->getItem( $id );
		}

		return new ItemsBatch( $batch );
	}

	private function getItem( ItemId $id ): ?Item {
		/** @var \Wikibase\DataModel\Entity\Item|null $item */
		$item = $this->entityLookup->getEntity( $id );
		'@phan-var \Wikibase\DataModel\Entity\Item|null $item';

		return $item ? new Item(
			$item->getId(),
			new Labels( ...$this->termListToLabelList( $item->getLabels() ) ),
			new Descriptions( ...$this->termListToDescriptionList( $item->getDescriptions() ) ),
			new Aliases( ...$this->aliasGroupListToAliasesInLanguageList( $item->getAliasGroups() ) ),
		) : null;
	}

	private function termListToLabelList( TermList $labels ): array {
		return array_map(
			fn( Term $t ) => new Label( $t->getLanguageCode(), $t->getText() ),
			iterator_to_array( $labels )
		);
	}

	private function termListToDescriptionList( TermList $descriptions ): array {
		return array_map(
			fn( Term $t ) => new Description( $t->getLanguageCode(), $t->getText() ),
			iterator_to_array( $descriptions )
		);
	}

	private function aliasGroupListToAliasesInLanguageList( AliasGroupList $aliasGroupList ): array {
		return array_map(
			fn( AliasGroup $g ) => new AliasesInLanguage( $g->getLanguageCode(), $g->getAliases() ),
			iterator_to_array( $aliasGroupList )
		);
	}
}
