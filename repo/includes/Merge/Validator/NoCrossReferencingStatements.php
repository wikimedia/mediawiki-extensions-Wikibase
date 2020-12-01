<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Merge\Validator;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
class NoCrossReferencingStatements {

	/** @var PropertyId[] */
	private $violations = [];

	public function validate( StatementListProvidingEntity $source, StatementListProvidingEntity $target ): bool {
		$this->violations = [];

		foreach ( $target->getStatements()->toArray() as $toStatement ) {
			$this->checkStatementHasLink( $toStatement, $source->getId() );
		}

		foreach ( $source->getStatements()->toArray() as $fromStatement ) {
			$this->checkStatementHasLink( $fromStatement, $target->getId() );
		}

		// @phan-suppress-next-line PhanRedundantCondition False positive
		return empty( $this->violations );
	}

	/**
	 * @return PropertyId[] Properties used to link across
	 */
	public function getViolations(): array {
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
