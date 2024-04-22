<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item as DataModelItem;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemCreator;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\SitelinksReadModelConverter;

/**
 * @license GPL-2.0-or-later
 */
class EntityUpdaterItemUpdater implements ItemUpdater, ItemCreator {

	private EntityUpdater $entityUpdater;
	private SitelinksReadModelConverter $sitelinksReadModelConverter;
	private StatementReadModelConverter $statementReadModelConverter;

	public function __construct(
		EntityUpdater $entityUpdater,
		SitelinksReadModelConverter $sitelinksReadModelConverter,
		StatementReadModelConverter $statementReadModelConverter
	) {
		$this->entityUpdater = $entityUpdater;
		$this->sitelinksReadModelConverter = $sitelinksReadModelConverter;
		$this->statementReadModelConverter = $statementReadModelConverter;
	}

	public function create( DataModelItem $item, EditMetadata $editMetadata ): ItemRevision {
		if ( $item->getId() ) {
			throw new InvalidArgumentException( 'new item cannot have an ID' );
		}
		return $this->convertToItemRevision( $this->entityUpdater->create( $item, $editMetadata ) );
	}

	public function update( DataModelItem $item, EditMetadata $editMetadata ): ItemRevision {
		if ( !$item->getId() ) {
			throw new InvalidArgumentException( 'updated item must have an ID' );
		}
		return $this->convertToItemRevision( $this->entityUpdater->update( $item, $editMetadata ) );
	}

	private function convertToItemRevision( EntityRevision $entityRevision ): ItemRevision {
		/** @var DataModelItem $savedItem */
		$savedItem = $entityRevision->getEntity();
		'@phan-var DataModelItem $savedItem';

		return new ItemRevision(
			$this->convertDataModelItemToReadModel( $savedItem ),
			$entityRevision->getTimestamp(),
			$entityRevision->getRevisionId()
		);
	}

	private function convertDataModelItemToReadModel( DataModelItem $item ): Item {
		return new Item(
			$item->getId(),
			Labels::fromTermList( $item->getLabels() ),
			Descriptions::fromTermList( $item->getDescriptions() ),
			Aliases::fromAliasGroupList( $item->getAliasGroups() ),
			$this->sitelinksReadModelConverter->convert( $item->getSiteLinkList() ),
			new StatementList(
				...array_map(
					[ $this->statementReadModelConverter, 'convert' ],
					iterator_to_array( $item->getStatements() )
				)
			)
		);
	}

}
