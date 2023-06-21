<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetStatement;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EntityIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetStatementValidatorTest extends TestCase {

	/**
	 * @dataProvider provideSubjectId
	 * @doesNotPerformAssertions
	 */
	public function testWithValidStatementId( string $subjectId ): void {
		$this->newStatementValidator()->assertValidRequest(
			new GetStatementRequest( "$subjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE" )
		);
	}

	/**
	 * @dataProvider provideSubjectId
	 * @doesNotPerformAssertions
	 */
	public function testWithValidStatementIdAndEntityId( string $subjectId ): void {
		$this->newStatementValidator()->assertValidRequest(
			new GetStatementRequest(
				"$subjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE",
				$subjectId
			)
		);
	}

	/**
	 * @dataProvider provideInvalidStatementId
	 */
	public function testWithInvalidStatementId( string $statementId ): void {
		try {
			$this->newStatementValidator()->assertValidRequest(
				new GetStatementRequest( $statementId )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_STATEMENT_ID, $e->getErrorCode() );
			$this->assertSame( "Not a valid statement ID: $statementId", $e->getErrorMessage() );
		}
	}

	public static function provideInvalidStatementId(): Generator {
		yield 'invalid format' => [ 'not-a-valid-statement-id' ];
		yield 'invalid subject id' => [ 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ];
		yield 'invalid UUID part with item subject' => [ 'Q123$INVALID-UUID-PART' ];
		yield 'invalid UUID part with property subject' => [ 'P123$INVALID-UUID-PART' ];
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testWithValidStatementIdAndInvalidSubjectId( string $validSubjectId ): void {
		$invalidSubjectId = 'X123';
		try {
			$this->newStatementValidator()->assertValidRequest(
				new GetStatementRequest(
					"$validSubjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE",
					$invalidSubjectId
				)
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_STATEMENT_SUBJECT_ID, $e->getErrorCode() );
			$this->assertSame( "Not a valid subject ID: $invalidSubjectId", $e->getErrorMessage() );
		}
	}

	public static function provideSubjectId(): Generator {
		yield 'item id' => [ 'Q123' ];
		yield 'property id' => [ 'P123' ];
	}

	private function newStatementValidator(): GetStatementValidator {
		return new GetStatementValidator(
			new StatementIdValidator( new BasicEntityIdParser() ),
			new EntityIdValidator( new BasicEntityIdParser() )
		);
	}

}
