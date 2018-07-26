<?php

namespace Wikibase\Repo\Merge\Validator;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * @license GPL-2.0-or-later
 */
class NoCrossReferencingStatements {

	private $violations = [];

	/**
	 * TODO Reasonably hint against both EntityDocument (for id) and StatementListProvider
	 * e.g. StatementListBearingEntityDocument
	 *
	 * @param StatementListProvider $source
	 * @param StatementListProvider $target
	 * @return bool
	 */
	public function validate( StatementListProvider $source, StatementListProvider $target ) {
		$this->violations = [];

		foreach ( $target->getStatements()->toArray() as $toStatement ) {
			$this->checkStatementHasLink( $toStatement, $source->getId() );
		}

		foreach ( $source->getStatements()->toArray() as $fromStatement ) {
			$this->checkStatementHasLink( $fromStatement, $target->getId() );
		}

		return empty( $this->violations );
	}

	/**
	 * @return PropertyId[] Properties used to link across
	 */
	public function getViolations() {
		return $this->violations;
	}

	private function checkStatementHasLink( Statement $statement, EntityId $id ) {
		$snaks = $statement->getAllSnaks();

		foreach ( $snaks as $snak ) {
			$this->checkSnakIsLink( $snak, $id );
		}
	}

	private function checkSnakIsLink( Snak $snak, EntityId $id ) {
		if ( !( $snak instanceof PropertyValueSnak ) ) {
			return;
		}

		$dataValue = $snak->getDataValue();

		if ( !( $dataValue instanceof EntityIdValue ) ) {
			return;
		}

		if ( $dataValue->getEntityId()->equals( $id ) ) {
			$this->violations[] = $snak->getPropertyId();
		}
	}

}
