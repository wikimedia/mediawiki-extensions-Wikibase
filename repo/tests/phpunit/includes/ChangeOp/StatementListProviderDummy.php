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

	public function getType() {
		return 'item';
	}

	public function getId() {
		return $this->id;
	}

	public function setId( $id ) {
		$this->id = $id;
	}

	public function isEmpty() {
		return true;
	}

	public function equals( $target ) {
		return true;
	}

	public function copy() {
		return $this;
	}

	public function getStatements() {
		return $this->statements;
	}

	public function clear() {
	}

}
