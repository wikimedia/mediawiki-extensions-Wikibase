<?php

namespace Wikibase\DataModel\Entity\Diff;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Statement\StatementListPatcher;

/**
 * @since 1.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemPatcher implements EntityPatcherStrategy {

	/**
	 * @var FingerprintPatcher
	 */
	private $fingerprintPatcher;

	/**
	 * @var StatementListPatcher
	 */
	private $statementListPatcher;

	/**
	 * @var SiteLinkListPatcher
	 */
	private $siteLinkListPatcher;

	public function __construct() {
		$this->fingerprintPatcher = new FingerprintPatcher();
		$this->statementListPatcher = new StatementListPatcher();
		$this->siteLinkListPatcher = new SiteLinkListPatcher();
	}

	/**
	 * @param string $entityType
	 *
	 * @return boolean
	 */
	public function canPatchEntityType( $entityType ) {
		return $entityType === 'item';
	}

	/**
	 * @param EntityDocument $entity
	 * @param EntityDiff $patch
	 *
	 * @return Item
	 * @throws InvalidArgumentException
	 */
	public function patchEntity( EntityDocument $entity, EntityDiff $patch ) {
		$this->assertIsItem( $entity );

		$this->patchItem( $entity, $patch );
	}

	private function assertIsItem( EntityDocument $item ) {
		if ( !( $item instanceof Item ) ) {
			throw new InvalidArgumentException( '$item must be an instance of Item' );
		}
	}

	private function patchItem( Item $item, EntityDiff $patch ) {
		$this->fingerprintPatcher->patchFingerprint( $item->getFingerprint(), $patch );

		if ( $patch instanceof ItemDiff ) {
			$item->setSiteLinkList( $this->siteLinkListPatcher->getPatchedSiteLinkList(
				$item->getSiteLinkList(),
				$patch->getSiteLinkDiff()
			) );
		}

		$item->setStatements( $this->statementListPatcher->getPatchedStatementList(
			$item->getStatements(),
			$patch->getClaimsDiff()
		) );
	}

}
