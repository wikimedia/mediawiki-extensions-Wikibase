<?php

namespace Wikibase\Test;

use Diff\ListDiffer;
use Wikibase\ClaimDiffer;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\ClaimSummaryBuilder;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * Tests for the ClaimSummaryBuilder class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @file
 * @since 0.4
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ClaimSummaryBuilder
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ClaimSummaryBuilderTest extends \MediaWikiTestCase {

	/**
	 * @return \Wikibase\Snak[]
	 */
	protected function snakProvider() {
		$snaks = array();

		$snaks[] = new \Wikibase\PropertyNoValueSnak( 42 );
		$snaks[] = new \Wikibase\PropertySomeValueSnak( 9001 );
		$snaks[] = new \Wikibase\PropertyValueSnak( 7201010, new \DataValues\StringValue( 'o_O' ) );

		return $snaks;
	}

	/**
	 * @return Claim[]
	 */
	protected function claimProvider() {
		$statements = array();

		$mainSnak = new \Wikibase\PropertyValueSnak( 112358, new \DataValues\StringValue( "don't panic" ) );
		$statement = new \Wikibase\Statement( $mainSnak );
		$statements[] = $statement;

		foreach ( $this->snakProvider() as $snak ) {
			$statement = clone $statement;
			$snaks = new \Wikibase\SnakList( array( $snak ) );
			$statement->getReferences()->addReference( new \Wikibase\Reference( $snaks ) );
			$statements[] = $statement;
		}

		$statement = clone $statement;
		$snaks = new \Wikibase\SnakList( $this->snakProvider() );
		$statement->getReferences()->addReference( new \Wikibase\Reference( $snaks ) );
		$statements[] = $statement;

		/**
		 * @var \Wikibase\Statement[] $statements
		 */
		foreach ( $statements as &$statement ) {
			$statement->setRank( \Wikibase\Statement::RANK_NORMAL );
		}

		return $statements;
	}

	public function buildUpdateClaimSummaryPovider() {
		$arguments = array();

		foreach ( $this->claimProvider() as $claim ) {
			$testCaseArgs = array();

			//change mainsnak
			$modifiedClaim = clone $claim;
			$modifiedClaim->setMainSnak( new \Wikibase\PropertyValueSnak( 112358, new \DataValues\StringValue( "let's panic!!!" ) ) );
			$testCaseArgs[] = $claim;
			$testCaseArgs[] = $modifiedClaim;
			$testCaseArgs[] = 'update';
			$arguments[] = $testCaseArgs;

			//change qualifiers
			$modifiedClaim = clone $claim;
			$modifiedClaim->setQualifiers( new \Wikibase\SnakList( $this->snakProvider() ) );
			$testCaseArgs[] = $claim;
			$testCaseArgs[] = $modifiedClaim;
			$testCaseArgs[] = 'update-qualifiers';
			$arguments[] = $testCaseArgs;

			//change rank
			$modifiedClaim = clone $claim;
			$modifiedClaim->setRank( \Wikibase\Statement::RANK_PREFERRED );
			$testCaseArgs[] = $claim;
			$testCaseArgs[] = $modifiedClaim;
			$testCaseArgs[] = 'update-rank';
			$arguments[] = $testCaseArgs;

			//change mainsnak & qualifiers
			$modifiedClaim = clone $claim;
			$modifiedClaim->setMainSnak( new \Wikibase\PropertyValueSnak( 112358, new \DataValues\StringValue( "let's panic!!!" ) ) );
			$modifiedClaim->setQualifiers( new \Wikibase\SnakList( $this->snakProvider() ) );
			$testCaseArgs[] = $claim;
			$testCaseArgs[] = $modifiedClaim;
			$testCaseArgs[] = 'update-rank';
			$arguments[] = $testCaseArgs;
		}

		return $arguments;
	}

	public function testBuildCreateClaimSummary() {
		$idFormatter = $this->getMockBuilder( 'Wikibase\Lib\EntityIdFormatter' )
			->disableOriginalConstructor()->getMock();
		$idFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( 'foo' ) );

		$claimSummaryBuilder = new ClaimSummaryBuilder(
			'wbsetclaim',
			new ClaimDiffer( new ListDiffer() ),
			$idFormatter
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
	 * @dataProvider buildUpdateClaimSummaryPovider
	 *
	 * @param Claim $originalClaim
	 * @param Claim $modifiedClaim
	 * @param string $action
	 */
	public function testBuildUpdateClaimSummary( $originalClaim, $modifiedClaim, $action ) {
		$idFormatter = $this->getMockBuilder( 'Wikibase\Lib\EntityIdFormatter' )
			->disableOriginalConstructor()->getMock();
		$idFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( 'foo' ) );

		$claimSummaryBuilder = new ClaimSummaryBuilder(
			'wbsetclaim',
			new ClaimDiffer( new ListDiffer() ),
			$idFormatter
		);

		$claims = new Claims();
		$claims->addClaim( $originalClaim );

		$summary = $claimSummaryBuilder->buildClaimSummary( $claims, $modifiedClaim );
		$this->assertInstanceOf( 'Wikibase\Summary', $summary, "this should return a Summary object" );
		$this->assertEquals( 'wbsetclaim', $summary->getModuleName() );
		$this->assertEquals( $action, $summary->getActionName() );
	}
}
