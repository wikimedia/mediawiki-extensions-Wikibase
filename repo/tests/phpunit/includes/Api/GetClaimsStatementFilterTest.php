<?php

namespace Wikibase\Repo\Tests\Api;

use RuntimeException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\GetClaimsStatementFilter;

/**
 * @covers \Wikibase\Repo\Api\GetClaimsStatementFilter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class GetClaimsStatementFilterTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @param bool $expectsError
	 *
	 * @return ApiErrorReporter
	 */
	private function getApiErrorReporter( $expectsError = false ) {
		$errorReporter = $this->createMock( ApiErrorReporter::class );

		if ( !$expectsError ) {
			$errorReporter->expects( $this->never() )
				->method( 'dieException' );
		} else {
			$errorReporter->expects( $this->once() )
				->method( 'dieException' )
				->with(
					$this->isInstanceOf( RuntimeException::class ),
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

		return [
			// No filter
			[ [], $statement, true ],

			// Filter by rank
			[ [ 'rank' => 'normal' ], $statement, true ],
			[ [ 'rank' => 'deprecated' ], $statement, false ],

			// Filter by property
			[ [ 'property' => 'p1' ], $statement, true ],
			[ [ 'property' => 'p2' ], $statement, false ],

			// Filter by both rank and property
			[ [ 'rank' => 'normal', 'property' => 'p1' ], $statement, true ],
			[ [ 'rank' => 'deprecated', 'property' => 'p1' ], $statement, false ],
			[ [ 'rank' => 'normal', 'property' => 'p2' ], $statement, false ],
		];
	}

	public function testInvalidRankSerialization() {
		$filter = new GetClaimsStatementFilter(
			new BasicEntityIdParser(),
			$this->getApiErrorReporter( true ),
			[ 'rank' => 'invalid' ]
		);

		$statement = new Statement( new PropertyNoValueSnak( 1 ) );
		$this->assertTrue( $filter->statementMatches( $statement ) );
	}

	public function testInvalidPropertySerialization() {
		$filter = new GetClaimsStatementFilter(
			new BasicEntityIdParser(),
			$this->getApiErrorReporter( true ),
			[ 'property' => 'invalid' ]
		);

		$statement = new Statement( new PropertyNoValueSnak( 1 ) );
		$this->assertTrue( $filter->statementMatches( $statement ) );
	}

}
