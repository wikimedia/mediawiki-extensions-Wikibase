<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests;

use DataValues\StringValue;
use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ClaimSummaryBuilder;
use Wikibase\Repo\Diff\ClaimDiffer;

/**
 * @covers \Wikibase\Repo\ClaimSummaryBuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ClaimSummaryBuilderTest extends TestCase {

	/**
	 * @return iterable<Snak>
	 */
	protected static function snakProvider(): iterable {
		yield 'novalue' => new PropertyNoValueSnak( 42 );
		yield 'somevalue' => new PropertySomeValueSnak( 9001 );
		yield 'value' => new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );
	}

	protected static function statementProvider(): iterable {
		foreach ( self::snakProvider() as $snakType => $snak ) {
			yield "statement with $snakType main snak" => new Statement( $snak );
		}

		$mainSnak = new PropertyValueSnak( 112358, new StringValue( "don't panic" ) );
		foreach ( self::snakProvider() as $snakType => $snak ) {
			yield "statement with $snakType qualifier snak" => new Statement(
				$mainSnak,
				new SnakList( [ $snak ] ),
			);
			yield "statement with $snakType reference snak" => new Statement(
				$mainSnak,
				references: new ReferenceList( [ new Reference( [ $snak ] ) ] ),
			);
		}
	}

	public static function buildUpdateClaimSummaryProvider(): iterable {
		$newMainSnak = new PropertyValueSnak( 112358, new StringValue( "let's panic!!!" ) );
		foreach ( self::statementProvider() as $name => $statement ) {
			$modifiedStatement = clone $statement;
			$modifiedStatement->setMainSnak( $newMainSnak );
			yield "change main snak of $name" => [
				'originalStatement' => $statement,
				'modifiedStatement' => $modifiedStatement,
				'action' => 'update',
			];

			$modifiedStatement = clone $statement;
			$modifiedStatement->setQualifiers( new SnakList( [ ...self::snakProvider() ] ) );
			yield "change qualifiers of $name" => [
				'originalStatement' => $statement,
				'modifiedStatement' => $modifiedStatement,
				'action' => 'update-qualifiers',
			];

			$modifiedStatement = clone $statement;
			$modifiedStatement->setReferences( new ReferenceList( [ new Reference( [ ...self::snakProvider() ] ) ] ) );
			yield "change references of $name" => [
				'originalStatement' => $statement,
				'modifiedStatement' => $modifiedStatement,
				'action' => 'update-references',
			];

			$modifiedStatement = clone $statement;
			$modifiedStatement->setRank( Statement::RANK_PREFERRED );
			yield "change rank of $name" => [
				'originalStatement' => $statement,
				'modifiedStatement' => $modifiedStatement,
				'action' => 'update-rank',
			];

			$modifiedStatement = clone $statement;
			$modifiedStatement->setMainSnak( $newMainSnak );
			$modifiedStatement->setQualifiers( new SnakList( [ ...self::snakProvider() ] ) );
			yield "change main snak and qualifiers of $name" => [
				'originalStatement' => $statement,
				'modifiedStatement' => $modifiedStatement,
				'action' => 'update',
			];
		}
	}

	public function testBuildCreateClaimSummary(): void {
		$claimSummaryBuilder = new ClaimSummaryBuilder(
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) )
		);

		$newStatements = $this->statementProvider();

		foreach ( $newStatements as $newStatement ) {
			$summary = $claimSummaryBuilder->buildClaimSummary( null, $newStatement );
			$this->assertInstanceOf( Summary::class, $summary );
			$this->assertEquals( 'wbsetclaim-create', $summary->getMessageKey() );
			$this->assertSame(
				[ [ $newStatement->getPropertyId()->getSerialization() => $newStatement->getMainSnak() ] ],
				$summary->getAutoSummaryArgs()
			);
		}
	}

	/**
	 * @dataProvider buildUpdateClaimSummaryProvider
	 */
	public function testBuildUpdateClaimSummary(
		Statement $originalStatement,
		Statement $modifiedStatement,
		string $action,
	): void {
		$claimSummaryBuilder = new ClaimSummaryBuilder(
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) )
		);

		$summary = $claimSummaryBuilder->buildClaimSummary( $originalStatement, $modifiedStatement );
		$this->assertInstanceOf( Summary::class, $summary );
		$this->assertEquals( 'wbsetclaim-' . $action, $summary->getMessageKey() );
		$this->assertSame(
			[ [ $modifiedStatement->getPropertyId()->getSerialization() => $modifiedStatement->getMainSnak() ] ],
			$summary->getAutoSummaryArgs()
		);
	}

}
