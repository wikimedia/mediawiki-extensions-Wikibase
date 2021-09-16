<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Normalization;

use DataValues\StringValue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Normalization\ReferenceNormalizer;
use Wikibase\Lib\Normalization\SnakNormalizer;
use Wikibase\Lib\Normalization\StatementNormalizer;

/**
 * @covers \Wikibase\Lib\Normalization\StatementNormalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementNormalizerTest extends TestCase {

	public function testNormalizeFullStatement(): void {
		$snakNormalizer = $this->createMock( SnakNormalizer::class );
		$snakNormalizer->method( 'normalize' )
			->willReturnCallback( static function ( PropertyValueSnak $snak ) {
				return new PropertyValueSnak(
					$snak->getPropertyId(),
					new StringValue( strtoupper( $snak->getDataValue()->getValue() ) )
				);
			} );
		$referenceNormalizer = new ReferenceNormalizer( $snakNormalizer );
		$statementNormalizer = new StatementNormalizer( $snakNormalizer, $referenceNormalizer );

		$p1 = new NumericPropertyId( 'P1' );
		$p2 = new NumericPropertyId( 'P2' );
		$guid = 'Q2013$0c0b84da-49d8-84ff-f367-0c6e5e098aa6';
		$statement = new Statement(
			new PropertyValueSnak( $p1, new StringValue( 'main' ) ),
			new SnakList( [
				new PropertyValueSnak( $p1, new StringValue( 'qualifier 1' ) ),
				new PropertyValueSnak( $p2, new StringValue( 'qualifier 2' ) ),
			] ),
			new ReferenceList( [
				new Reference( [
					new PropertyValueSnak( $p1, new StringValue( 'reference 1-1' ) ),
					new PropertyValueSnak( $p2, new StringValue( 'reference 1-2' ) ),
				] ),
				new Reference( [
					new PropertyValueSnak( $p2, new StringValue( 'reference 2-1' ) ),
				] ),
			] ),
			$guid
		);
		$statement->setRank( Statement::RANK_PREFERRED );
		$normalized = $statementNormalizer->normalize( $statement );

		$this->assertSame( $guid, $normalized->getGuid() );
		$this->assertSame( Statement::RANK_PREFERRED, $normalized->getRank() );
		$this->assertSame( 'MAIN', $normalized->getMainSnak()->getDataValue()->getValue() );
		$this->assertSame( 'QUALIFIER 1', $normalized->getQualifiers()[0]->getDataValue()->getValue() );
		$this->assertSame( 'QUALIFIER 2', $normalized->getQualifiers()[1]->getDataValue()->getValue() );
		$references = iterator_to_array( $normalized->getReferences()->getIterator() );
		$this->assertSame( 'REFERENCE 1-1', $references[0]->getSnaks()[0]->getDataValue()->getValue() );
		$this->assertSame( 'REFERENCE 1-2', $references[0]->getSnaks()[1]->getDataValue()->getValue() );
		$this->assertSame( 'REFERENCE 2-1', $references[1]->getSnaks()[0]->getDataValue()->getValue() );
	}

}
