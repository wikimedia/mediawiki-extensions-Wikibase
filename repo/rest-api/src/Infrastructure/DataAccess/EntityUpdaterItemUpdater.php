<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\Item as DataModelItem;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;

/**
 * @license GPL-2.0-or-later
 */
class EntityUpdaterItemUpdater implements ItemUpdater {

	private EntityUpdater $entityUpdater;
	private StatementReadModelConverter $statementReadModelConverter;

	public function __construct( EntityUpdater $entityUpdater, StatementReadModelConverter $statementReadModelConverter ) {
		$this->entityUpdater = $entityUpdater;
		$this->statementReadModelConverter = $statementReadModelConverter;
	}

	public function update( DataModelItem $item, EditMetadata $editMetadata ): ItemRevision {
		$entityRevision = $this->entityUpdater->update( $item, $editMetadata );

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
			Labels::fromTermList( $item->getLabels() ),
			Descriptions::fromTermList( $item->getDescriptions() ),
			Aliases::fromAliasGroupList( $item->getAliasGroups() ),
			new StatementList(
				...array_map(
					[ $this->statementReadModelConverter, 'convert' ],
					iterator_to_array( $item->getStatements() )
				)
			)
		);
	}

}
