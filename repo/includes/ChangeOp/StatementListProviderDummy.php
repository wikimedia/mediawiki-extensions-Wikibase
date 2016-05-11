<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * A dummy entity for tests only.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class StatementListProviderDummy implements EntityDocument, StatementListProvider {

	private $id;

	/**
	 * @param string $id
	 */
	function __construct( $id ) {
		$this->id = new ItemId( $id );
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
		return new StatementList();
	}

}
