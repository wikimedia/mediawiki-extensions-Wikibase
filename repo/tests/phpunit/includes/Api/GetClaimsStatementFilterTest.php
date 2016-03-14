<?php

namespace Wikibase\DataModel\Services\Tests\Statement\Filter;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\GetClaimsStatementFilter;

/**
 * @covers Wikibase\Repo\Api\GetClaimsStatementFilter
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class GetClaimsStatementFilterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param bool $expectsError
	 *
	 * @return ApiErrorReporter
	 */
	private function getApiErrorReporter( $expectsError = false ) {
		$errorReporter = $this->getMockBuilder( ApiErrorReporter::class )
			->disableOriginalConstructor()
			->getMock();

		if ( !$expectsError ) {
			$errorReporter->expects( $this->never() )
				->method( 'dieException' );
		} else {
			$errorReporter->expects( $this->once() )
				->method( 'dieException' )
				->with(
					$this->isInstanceOf( 'RuntimeException' ),
					'param-invalid'
				);
		}

		return $errorReporter;
	}

	/**
	 * @dataProvider statementProvider
	 */
	public function testIsMatch( array $requestParams, Statement $statement, $expected ) {
		$filter = new GetClaimsStatementFilter(
			new BasicEntityIdParser(),
			$this->getApiErrorReporter(),
			$requestParams
		);

		$this->assertSame( $expected, $filter->statementMatches( $statement ) );
	}

	public function statementProvider() {
		$statement = new Statement( new PropertyNoValueSnak( 1 ) );

		return array(
			// No filter
			array( array(), $statement, true ),

			// Filter by rank
			array( array( 'rank' => 'normal' ), $statement, true ),
			array( array( 'rank' => 'deprecated' ), $statement, false ),

			// Filter by property
			array( array( 'property' => 'p1' ), $statement, true ),
			array( array( 'property' => 'p2' ), $statement, false ),

			// Filter by both rank and property
			array( array( 'rank' => 'normal', 'property' => 'p1' ), $statement, true ),
			array( array( 'rank' => 'deprecated', 'property' => 'p1' ), $statement, false ),
			array( array( 'rank' => 'normal', 'property' => 'p2' ), $statement, false ),
		);
	}

	public function testInvalidRankSerialization() {
		$filter = new GetClaimsStatementFilter(
			new BasicEntityIdParser(),
			$this->getApiErrorReporter( true ),
			array( 'rank' => 'invalid' )
		);

		$statement = new Statement( new PropertyNoValueSnak( 1 ) );
		$this->assertTrue( $filter->statementMatches( $statement ) );
	}

	public function testInvalidPropertySerialization() {
		$filter = new GetClaimsStatementFilter(
			new BasicEntityIdParser(),
			$this->getApiErrorReporter( true ),
			array( 'property' => 'invalid' )
		);

		$statement = new Statement( new PropertyNoValueSnak( 1 ) );
		$this->assertTrue( $filter->statementMatches( $statement ) );
	}

}
