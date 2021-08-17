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
		$this->assertIsProperty( $from );
		$this->assertIsProperty( $to );

		return $this->diffProperties( $from, $to );
	}

	private function assertIsProperty( EntityDocument $property ) {
		if ( !( $property instanceof Property ) ) {
			throw new InvalidArgumentException( '$property must be an instance of Property' );
		}
	}

	public function diffProperties( Property $from, Property $to ) {
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
		$this->assertIsProperty( $entity );

		/** @var Property $entity */
		$diffOps = $this->diffPropertyArrays( [], $this->toDiffArray( $entity ) );
		$diffOps['claim'] = $this->statementListDiffer->getDiff( new StatementList(), $entity->getStatements() );

		return new EntityDiff( $diffOps );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function getDestructionDiff( EntityDocument $entity ) {
		$this->assertIsProperty( $entity );

		/** @var Property $entity */
		$diffOps = $this->diffPropertyArrays( $this->toDiffArray( $entity ), [] );
		$diffOps['claim'] = $this->statementListDiffer->getDiff( $entity->getStatements(), new StatementList() );

		return new EntityDiff( $diffOps );
	}

}
