<?php

namespace Wikibase\DataModel\Services\Diff;

use Diff\Differ\MapDiffer;
use Diff\DiffOp\DiffOp;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyDiffer implements EntityDifferStrategy {

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
		return $entityType === 'property';
	}

	/**
	 * @param EntityDocument $from
	 * @param EntityDocument $to
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function diffEntities( EntityDocument $from, EntityDocument $to ) {
		$fromProperty = $this->assertIsPropertyAndCast( $from );
		$toProperty = $this->assertIsPropertyAndCast( $to );

		return $this->diffProperties( $fromProperty, $toProperty );
	}

	private function assertIsPropertyAndCast( EntityDocument $property ): Property {
		if ( !( $property instanceof Property ) ) {
			throw new InvalidArgumentException( '$property must be an instance of Property' );
		}
		return $property;
	}

	public function diffProperties( Property $from, Property $to ): EntityDiff {
		$diffOps = $this->diffPropertyArrays(
			$this->toDiffArray( $from ),
			$this->toDiffArray( $to )
		);

		$diffOps['claim'] = $this->statementListDiffer->getDiff( $from->getStatements(), $to->getStatements() );

		return new EntityDiff( $diffOps );
	}

	/**
	 * @param array[] $from
	 * @param array[] $to
	 *
	 * @return DiffOp[]
	 */
	private function diffPropertyArrays( array $from, array $to ) {
		return $this->recursiveMapDiffer->doDiff( $from, $to );
	}

	/**
	 * @param Property $property
	 *
	 * @return array[]
	 */
	private function toDiffArray( Property $property ) {
		$array = [];

		$array['aliases'] = $property->getAliasGroups()->toTextArray();
		$array['label'] = $property->getLabels()->toTextArray();
		$array['description'] = $property->getDescriptions()->toTextArray();

		return $array;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function getConstructionDiff( EntityDocument $entity ) {
		$property = $this->assertIsPropertyAndCast( $entity );

		$diffOps = $this->diffPropertyArrays( [], $this->toDiffArray( $property ) );
		$diffOps['claim'] = $this->statementListDiffer->getDiff( new StatementList(), $property->getStatements() );

		return new EntityDiff( $diffOps );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function getDestructionDiff( EntityDocument $entity ) {
		$property = $this->assertIsPropertyAndCast( $entity );

		$diffOps = $this->diffPropertyArrays( $this->toDiffArray( $property ), [] );
		$diffOps['claim'] = $this->statementListDiffer->getDiff( $property->getStatements(), new StatementList() );

		return new EntityDiff( $diffOps );
	}

}
