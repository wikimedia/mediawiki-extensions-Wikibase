<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\ChangeOp;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * A dummy entity for tests only.
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class StatementListProviderDummy implements EntityDocument, StatementListProvider {

	/** @var ItemId */
	private $id;

	/** @var StatementList */
	private $statements;

	public function __construct( string $id ) {
		$this->id = new ItemId( $id );
		$this->statements = new StatementList();
	}

	/** @inheritDoc */
	public function getType() {
		return 'item';
	}

	/** @inheritDoc */
	public function getId() {
		return $this->id;
	}

	/** @inheritDoc */
	public function setId( $id ) {
		$this->id = $id;
	}

	/** @inheritDoc */
	public function isEmpty() {
		return true;
	}

	/** @inheritDoc */
	public function equals( $target ) {
		return true;
	}

	/** @inheritDoc */
	public function copy() {
		return $this;
	}

	/** @inheritDoc */
	public function getStatements() {
		return $this->statements;
	}

	/** @inheritDoc */
	public function clear() {
	}

}
