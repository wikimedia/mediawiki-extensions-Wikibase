<?php

namespace Wikibase\DataModel\Services\Diff;

use Diff\Differ\MapDiffer;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;

/**
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemDiffer implements EntityDifferStrategy {

	/**
	 * @var MapDiffer
	 */
	private $recursiveMapDiffer;

	/**
	 * @var StatementListDiffer
	 */
	private $statementListDiffer;

	public function __construct() {
		$this->recursiveMapDiffer = new MapDiffer( true );
		$this->statementListDiffer = new StatementListDiffer();
	}

	/**
	 * @param string $entityType
	 *
	 * @return bool
	 */
	public function canDiffEntityType( $entityType ) {
		return $entityType === 'item';
	}

	/**
	 * @param EntityDocument $from
	 * @param EntityDocument $to
	 *
	 * @return ItemDiff
	 * @throws InvalidArgumentException
	 */
	public function diffEntities( EntityDocument $from, EntityDocument $to ) {
		$fromItem = $this->assertIsItemAndCast( $from );
		$toItem = $this->assertIsItemAndCast( $to );

		return $this->diffItems( $fromItem, $toItem );
	}

	private function assertIsItemAndCast( EntityDocument $item ): Item {
		if ( !( $item instanceof Item ) ) {
			throw new InvalidArgumentException( '$item must be an instance of Item' );
		}
		return $item;
	}

	public function diffItems( Item $from, Item $to ): ItemDiff {
		$diffOps = $this->recursiveMapDiffer->doDiff(
			$this->toDiffArray( $from ),
			$this->toDiffArray( $to )
		);

		$diffOps['claim'] = $this->statementListDiffer->getDiff( $from->getStatements(), $to->getStatements() );

		return new ItemDiff( $diffOps );
	}

	private function toDiffArray( Item $item ): array {
		$array = [];

		$array['aliases'] = $item->getAliasGroups()->toTextArray();
		$array['label'] = $item->getLabels()->toTextArray();
		$array['description'] = $item->getDescriptions()->toTextArray();
		$array['links'] = $this->getSiteLinksInDiffFormat( $item->getSiteLinkList() );

		return $array;
	}

	private function getSiteLinksInDiffFormat( SiteLinkList $siteLinks ): array {
		$linksInDiffFormat = [];

		foreach ( $siteLinks->toArray() as $siteLink ) {
			$linksInDiffFormat[$siteLink->getSiteId()] = [
				'name' => $siteLink->getPageName(),
				'badges' => array_map(
					static function( ItemId $id ) {
						return $id->getSerialization();
					},
					$siteLink->getBadges()
				),
			];
		}

		return $linksInDiffFormat;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return ItemDiff
	 * @throws InvalidArgumentException
	 */
	public function getConstructionDiff( EntityDocument $entity ) {
		$item = $this->assertIsItemAndCast( $entity );
		return $this->diffEntities( new Item(), $item );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return ItemDiff
	 * @throws InvalidArgumentException
	 */
	public function getDestructionDiff( EntityDocument $entity ) {
		$item = $this->assertIsItemAndCast( $entity );
		return $this->diffEntities( $item, new Item() );
	}

}
