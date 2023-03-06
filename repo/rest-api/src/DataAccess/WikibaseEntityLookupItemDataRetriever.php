<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Statement\StatementList as DataModelStatementList;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemData;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemDataBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemDataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\SiteLinksReadModelConverter;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseEntityLookupItemDataRetriever	implements ItemRetriever, ItemDataRetriever, ItemStatementsRetriever, ItemStatementRetriever {

	private EntityLookup $entityLookup;
	private StatementReadModelConverter $statementReadModelConverter;
	private SiteLinksReadModelConverter $siteLinksReadModelConverter;

	public function __construct(
		EntityLookup $entityLookup,
		StatementReadModelConverter $statementReadModelConverter,
		SiteLinksReadModelConverter $siteLinksReadModelConverter
	) {
		$this->entityLookup = $entityLookup;
		$this->statementReadModelConverter = $statementReadModelConverter;
		$this->siteLinksReadModelConverter = $siteLinksReadModelConverter;
	}

	public function getItem( ItemId $itemId ): ?Item {
		/** @var Item $item */
		$item = $this->entityLookup->getEntity( $itemId );
		'@phan-var Item $item';

		return $item;
	}

	public function getItemData( ItemId $itemId, array $fields ): ?ItemData {
		$item = $this->getItem( $itemId );
		if ( $item === null ) {
			return null;
		}
		return $this->itemDataFromRequestedFields( $fields, $item );
	}

	private function itemDataFromRequestedFields( array $fields, Item $item ): ItemData {
		$itemData = ( new ItemDataBuilder( $item->getId(), $fields ) );

		if ( in_array( ItemData::FIELD_TYPE, $fields ) ) {
			$itemData->setType( $item->getType() );
		}
		if ( in_array( ItemData::FIELD_LABELS, $fields ) ) {
			$itemData->setLabels( Labels::fromTermList( $item->getLabels() ) );
		}
		if ( in_array( ItemData::FIELD_DESCRIPTIONS, $fields ) ) {
			$itemData->setDescriptions( Descriptions::fromTermList( $item->getDescriptions() ) );
		}
		if ( in_array( ItemData::FIELD_ALIASES, $fields ) ) {
			$itemData->setAliases( Aliases::fromAliasGroupList( $item->getAliasGroups() ) );
		}
		if ( in_array( ItemData::FIELD_STATEMENTS, $fields ) ) {
			$itemData->setStatements( $this->convertDataModelStatementListToReadModel( $item->getStatements() ) );
		}
		if ( in_array( ItemData::FIELD_SITELINKS, $fields ) ) {
			$itemData->setSiteLinks( $this->siteLinksReadModelConverter->convert( $item->getSiteLinkList() ) );
		}

		return $itemData->build();
	}

	public function getStatements( ItemId $itemId, ?PropertyId $propertyId = null ): ?StatementList {
		$item = $this->getItem( $itemId );
		if ( $item === null ) {
			return null;
		}

		return $this->convertDataModelStatementListToReadModel(
			$propertyId ? $item->getStatements()->getByPropertyId( $propertyId ) : $item->getStatements()
		);
	}

	private function convertDataModelStatementListToReadModel( DataModelStatementList $list ): StatementList {
		return new StatementList( ...array_map(
			[ $this->statementReadModelConverter, 'convert' ],
			iterator_to_array( $list )
		) );
	}

	public function getStatement( StatementGuid $statementGuid ): ?Statement {
		/** @var ItemId $itemId */
		$itemId = $statementGuid->getEntityId();
		'@phan-var ItemId $itemId';

		$statements = $this->getStatements( $itemId );
		if ( $statements === null ) {
			return null;
		}

		return $statements->getStatementById( $statementGuid );
	}
}
