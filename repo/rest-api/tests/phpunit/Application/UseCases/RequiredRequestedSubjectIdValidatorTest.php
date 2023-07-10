<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\RequiredRequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EntityIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RequiredRequestedSubjectIdValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RequiredRequestedSubjectIdValidatorTest extends TestCase {

	/**
	 * @dataProvider provideEntityIdValidatorAndValidSubjectId
	 * @doesNotPerformAssertions
	 */
	public function testGivenValidSubjectIdForValidator_noErrorIsThrown( EntityIdValidator $idValidator, string $subjectId ): void {
		$this->newRequiredRequestedSubjectIdValidator( $idValidator )->assertValid( $subjectId );
	}

	public function provideEntityIdValidatorAndValidSubjectId(): Generator {
		yield 'item id' => [ new ItemIdValidator(), 'Q123' ];
		yield 'property id' => [ new PropertyIdValidator(), 'P123' ];
	}

	/**
	 * @dataProvider provideEntityIdValidatorAndInvalidSubjectId
	 */
	public function testGivenInvalidSubjectIdForValidator_throwsUseCaseError( EntityIdValidator $idValidator, string $subjectId ): void {
		try {
			$this->newRequiredRequestedSubjectIdValidator( $idValidator )->assertValid( $subjectId );
			$this->fail( 'Exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_STATEMENT_SUBJECT_ID, $e->getErrorCode() );
		}
	}

	public function provideEntityIdValidatorAndInvalidSubjectId(): Generator {
		yield 'ItemIdValidator with Property ID' => [ new ItemIdValidator(), 'P123' ];
		yield 'ItemIdValidator with Invalid ID' => [ new ItemIdValidator(), 'X123' ];
		yield 'PropertyIdValidator with Item ID' => [ new PropertyIdValidator(), 'Q123' ];
		yield 'PropertyIdValidator with Invalid ID' => [ new PropertyIdValidator(), 'X123' ];
	}

	public function testGivenSubjectIdIsNull_throwsLogicException(): void {
		$this->expectException( LogicException::class );
		$this->newRequiredRequestedSubjectIdValidator( $this->createStub( EntityIdValidator::class ) )->assertValid( null );
	}

	private function newRequiredRequestedSubjectIdValidator( EntityIdValidator $idValidator ): RequiredRequestedSubjectIdValidator {
		return new RequiredRequestedSubjectIdValidator( $idValidator );
	}

}
