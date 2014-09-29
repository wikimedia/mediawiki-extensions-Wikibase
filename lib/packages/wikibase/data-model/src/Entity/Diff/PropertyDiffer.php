<?php

namespace Wikibase\DataModel\Entity\Diff;

use Diff\Differ\MapDiffer;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListDiffer;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
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

	private function assertIsProperty( EntityDocument $item ) {
		if ( !( $item instanceof Property ) ) {
			throw new InvalidArgumentException( 'All entities need to be properties' );
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

	private function diffPropertyArrays( array $from, array $to ) {
		return $this->recursiveMapDiffer->doDiff( $from, $to );
	}

	private function toDiffArray( Property $item ) {
		$array = array();

		$array['aliases'] = $item->getAllAliases();
		$array['label'] = $item->getLabels();
		$array['description'] = $item->getDescriptions();

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

		$diffOps = $this->diffPropertyArrays( array(), $this->toDiffArray( $entity ) );
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

		$diffOps = $this->diffPropertyArrays( $this->toDiffArray( $entity ), array() );
		$diffOps['claim'] = $this->statementListDiffer->getDiff( $entity->getStatements(), new StatementList() );

		return new EntityDiff( $diffOps );
	}

}