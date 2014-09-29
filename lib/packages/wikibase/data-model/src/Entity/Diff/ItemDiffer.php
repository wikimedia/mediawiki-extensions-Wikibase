<?php

namespace Wikibase\DataModel\Entity\Diff;

use Diff\Differ\MapDiffer;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Statement\StatementListDiffer;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
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
			throw new InvalidArgumentException( 'All entities need to be items' );
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
		$array = array();

		$array['aliases'] = $item->getAllAliases();
		$array['label'] = $item->getLabels();
		$array['description'] = $item->getDescriptions();
		$array['links'] = $this->getLinksInDiffFormat( $item );

		return $array;
	}

	private function getLinksInDiffFormat( Item $item ) {
		$links = array();

		/**
		 * @var SiteLink $siteLink
		 */
		foreach ( $item->getSiteLinkList() as $siteLink ) {
			$links[$siteLink->getSiteId()] = array(
				'name' => $siteLink->getPageName(),
				'badges' => array_map(
					function( ItemId $id ) {
						return $id->getSerialization();
					},
					$siteLink->getBadges()
				)
			);
		}

		return $links;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return ItemDiff
	 * @throws InvalidArgumentException
	 */
	public function getConstructionDiff( EntityDocument $entity ) {
		$this->assertIsItem( $entity );
		return $this->diffEntities( Item::newEmpty(), $entity );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return ItemDiff
	 * @throws InvalidArgumentException
	 */
	public function getDestructionDiff( EntityDocument $entity ) {
		$this->assertIsItem( $entity );
		return $this->diffEntities( $entity, Item::newEmpty() );
	}

}
