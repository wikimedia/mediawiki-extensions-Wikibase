<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use Wikibase\ClaimSummaryBuilder;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Diff\ClaimDiffer;

/**
 * @covers Wikibase\ClaimSummaryBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ClaimSummaryBuilder
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ClaimSummaryBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return Snak[]
	 */
	protected function snakProvider() {
		$snaks = array();

		$snaks[] = new PropertyNoValueSnak( 42 );
		$snaks[] = new PropertySomeValueSnak( 9001 );
		$snaks[] = new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );

		return $snaks;
	}

	/**
	 * @return Claim[]
	 */
	protected function claimProvider() {
		$statements = array();

		$mainSnak = new PropertyValueSnak( 112358, new StringValue( "don't panic" ) );
		$statement = new Statement( new Claim( $mainSnak ) );
		$statements[] = $statement;

		foreach ( $this->snakProvider() as $snak ) {
			$statement = clone $statement;
			$snaks = new SnakList( array( $snak ) );
			$statement->getReferences()->addReference( new Reference( $snaks ) );
			$statements[] = $statement;
		}

		$statement = clone $statement;
		$snaks = new SnakList( $this->snakProvider() );
		$statement->getReferences()->addReference( new Reference( $snaks ) );
		$statements[] = $statement;

		/**
		 * @var Statement[] $statements
		 */

		$i = 0;
		foreach ( $statements as &$statement ) {
			$i++;
			$guid = "Q{$i}\$7{$i}d";

			$statement->setGuid( $guid );
			$statement->setRank( Statement::RANK_NORMAL );
		}

		return $statements;
	}

	public function buildUpdateClaimSummaryProvider() {
		$arguments = array();

		foreach ( $this->claimProvider() as $claim ) {
			$testCaseArgs = array();

			//change mainsnak
			$modifiedClaim = clone $claim;
			$modifiedClaim->setMainSnak(
				new PropertyValueSnak( 112358, new StringValue( "let's panic!!!" ) )
			);
			$testCaseArgs[] = $claim;
			$testCaseArgs[] = $modifiedClaim;
			$testCaseArgs[] = 'update';
			$arguments[] = $testCaseArgs;

			//change qualifiers
			$modifiedClaim = clone $claim;
			$modifiedClaim->setQualifiers( new SnakList( $this->snakProvider() ) );
			$testCaseArgs[] = $claim;
			$testCaseArgs[] = $modifiedClaim;
			$testCaseArgs[] = 'update-qualifiers';
			$arguments[] = $testCaseArgs;

			//change rank
			$modifiedClaim = clone $claim;
			$modifiedClaim->setRank( Statement::RANK_PREFERRED );
			$testCaseArgs[] = $claim;
			$testCaseArgs[] = $modifiedClaim;
			$testCaseArgs[] = 'update-rank';
			$arguments[] = $testCaseArgs;

			//change mainsnak & qualifiers
			$modifiedClaim = clone $claim;
			$modifiedClaim->setMainSnak(
				new PropertyValueSnak( 112358, new StringValue( "let's panic!!!" ) )
			);
			$modifiedClaim->setQualifiers( new SnakList( $this->snakProvider() ) );
			$testCaseArgs[] = $claim;
			$testCaseArgs[] = $modifiedClaim;
			$testCaseArgs[] = 'update-rank';
			$arguments[] = $testCaseArgs;
		}

		return $arguments;
	}

	public function testBuildCreateClaimSummary() {
		$claimSummaryBuilder = new ClaimSummaryBuilder(
			'wbsetclaim',
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) )
		);

		$claims = new Claims();
		$newClaims = $this->claimProvider();

		foreach ( $newClaims as $newClaim ) {
			$summary = $claimSummaryBuilder->buildClaimSummary( $claims, $newClaim );
			$this->assertInstanceOf( 'Wikibase\Summary', $summary, "this should return a Summary object" );
			$this->assertEquals( 'wbsetclaim', $summary->getModuleName() );
			$this->assertEquals( 'create', $summary->getActionName() );
		}
	}

	/**
	 * @dataProvider buildUpdateClaimSummaryProvider
	 *
	 * @param Claim $originalClaim
	 * @param Claim $modifiedClaim
	 * @param string $action
	 */
	public function testBuildUpdateClaimSummary( $originalClaim, $modifiedClaim, $action ) {
		$claimSummaryBuilder = new ClaimSummaryBuilder(
			'wbsetclaim',
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) )
		);

		$claims = new Claims();
		$claims->addClaim( $originalClaim );

		$summary = $claimSummaryBuilder->buildClaimSummary( $claims, $modifiedClaim );
		$this->assertInstanceOf( 'Wikibase\Summary', $summary, "this should return a Summary object" );
		$this->assertEquals( 'wbsetclaim', $summary->getModuleName() );
		$this->assertEquals( $action, $summary->getActionName() );
	}

}
