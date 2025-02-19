<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item as ItemWriteModel;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Aliases;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Item;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemCreator;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\Domains\Crud\Infrastructure\SitelinksReadModelConverter;

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

	public function create( ItemWriteModel $item, EditMetadata $editMetadata ): ItemRevision {
		if ( $item->getId() ) {
			throw new InvalidArgumentException( 'new item cannot have an ID' );
		}
		return $this->convertToItemRevision( $this->entityUpdater->create( $item, $editMetadata ) );
	}

	public function update( ItemWriteModel $item, EditMetadata $editMetadata ): ItemRevision {
		if ( !$item->getId() ) {
			throw new InvalidArgumentException( 'updated item must have an ID' );
		}
		return $this->convertToItemRevision( $this->entityUpdater->update( $item, $editMetadata ) );
	}

	private function convertToItemRevision( EntityRevision $entityRevision ): ItemRevision {
		/** @var ItemWriteModel $savedItem */
		$savedItem = $entityRevision->getEntity();
		'@phan-var ItemWriteModel $savedItem';

		return new ItemRevision(
			$this->convertItemWriteModelToReadModel( $savedItem ),
			$entityRevision->getTimestamp(),
			$entityRevision->getRevisionId()
		);
	}

	private function convertItemWriteModelToReadModel( ItemWriteModel $item ): Item {
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
