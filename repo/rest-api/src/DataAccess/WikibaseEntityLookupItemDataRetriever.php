<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\RestApi\Domain\Model\ItemData;
use Wikibase\Repo\RestApi\Domain\Model\ItemDataBuilder;
use Wikibase\Repo\RestApi\Domain\Services\ItemDataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseEntityLookupItemDataRetriever implements ItemDataRetriever {

	private $entityLookup;

	public function __construct( EntityLookup $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @throws StorageException
	 */
	public function getItemData( ItemId $itemId, array $fields ): ?ItemData {
		/** @var Item $item */
		$item = $this->entityLookup->getEntity( $itemId );
		'@phan-var Item $item';

		if ( $item === null ) {
			return null;
		}
		return $this->itemDataFromFields( $fields, $item );
	}

	private function itemDataFromFields( array $fields, Item $item ): ItemData {
		$itemData = ( new ItemDataBuilder() )->setId( $item->getId() );

		if ( in_array( ItemData::FIELD_TYPE, $fields ) ) {
			$itemData->setType( $item->getType() );
		}
		if ( in_array( ItemData::FIELD_LABELS, $fields ) ) {
			$itemData->setLabels( $item->getLabels() );
		}
		if ( in_array( ItemData::FIELD_DESCRIPTIONS, $fields ) ) {
			$itemData->setDescriptions( $item->getDescriptions() );
		}
		if ( in_array( ItemData::FIELD_ALIASES, $fields ) ) {
			$itemData->setAliases( $item->getAliasGroups() );
		}
		if ( in_array( ItemData::FIELD_STATEMENTS, $fields ) ) {
			$itemData->setStatements( $item->getStatements() );
		}
		if ( in_array( ItemData::FIELD_SITELINKS, $fields ) ) {
			$itemData->setSiteLinks( $item->getSiteLinkList() );
		}

		return $itemData->build();
	}
}
