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
		$this->assertIsItem( $from );
		$this->assertIsItem( $to );

		return $this->diffItems( $from, $to );
	}

	private function assertIsItem( EntityDocument $item ) {
		if ( !( $item instanceof Item ) ) {
			throw new InvalidArgumentException( '$item must be an instance of Item' );
		}
	}

	public function diffItems( Item $from, Item $to ) {
		$diffOps = $this->recursiveMapDiffer->doDiff(
			$this->toDiffArray( $from ),
			$this->toDiffArray( $to )
		);

		$diffOps['claim'] = $this->statementListDiffer->getDiff( $from->getStatements(), $to->getStatements() );

		return new ItemDiff( $diffOps );
	}

	private function toDiffArray( Item $item ) {
		$array = [];

		$array['aliases'] = $item->getAliasGroups()->toTextArray();
		$array['label'] = $item->getLabels()->toTextArray();
		$array['description'] = $item->getDescriptions()->toTextArray();
		$array['links'] = $this->getSiteLinksInDiffFormat( $item->getSiteLinkList() );

		return $array;
	}

	private function getSiteLinksInDiffFormat( SiteLinkList $siteLinks ) {
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
		$this->assertIsItem( $entity );
		return $this->diffEntities( new Item(), $entity );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return ItemDiff
	 * @throws InvalidArgumentException
	 */
	public function getDestructionDiff( EntityDocument $entity ) {
		$this->assertIsItem( $entity );
		return $this->diffEntities( $entity, new Item() );
	}

}
