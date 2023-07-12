<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Validation\EntityIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\RequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\RequiredRequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\RequiredRequestedSubjectIdValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RequiredRequestedSubjectIdValidatorTest extends TestCase {

	/**
	 * @dataProvider provideEntityIdValidatorAndValidSubjectId
	 */
	public function testGivenValidSubjectIdForValidator_returnsNull( EntityIdValidator $idValidator, string $subjectId ): void {
		$this->assertNull( $this->newRequiredRequestedSubjectIdValidator( $idValidator )->validate( $subjectId ) );
	}

	public function provideEntityIdValidatorAndValidSubjectId(): Generator {
		yield 'item id' => [ new ItemIdValidator(), 'Q123' ];
		yield 'property id' => [ new PropertyIdValidator(), 'P123' ];
	}

	/**
	 * @dataProvider provideEntityIdValidatorAndInvalidSubjectId
	 */
	public function testGivenInvalidSubjectIdForValidator_returnsValidationError( EntityIdValidator $validator, string $subjectId ): void {
		$this->assertEquals(
			new ValidationError( RequestedSubjectIdValidator::CODE_INVALID, [ RequestedSubjectIdValidator::CONTEXT_VALUE => $subjectId ] ),
			$this->newRequiredRequestedSubjectIdValidator( $validator )->validate( $subjectId )
		);
	}

	public function provideEntityIdValidatorAndInvalidSubjectId(): Generator {
		yield 'ItemIdValidator with Property ID' => [ new ItemIdValidator(), 'P123' ];
		yield 'ItemIdValidator with Invalid ID' => [ new ItemIdValidator(), 'X123' ];
		yield 'PropertyIdValidator with Item ID' => [ new PropertyIdValidator(), 'Q123' ];
		yield 'PropertyIdValidator with Invalid ID' => [ new PropertyIdValidator(), 'X123' ];
	}

	public function testGivenSubjectIdIsNull_throwsLogicException(): void {
		$this->expectException( LogicException::class );
		$this->newRequiredRequestedSubjectIdValidator( $this->createStub( EntityIdValidator::class ) )->validate( null );
	}

	private function newRequiredRequestedSubjectIdValidator( EntityIdValidator $idValidator ): RequiredRequestedSubjectIdValidator {
		return new RequiredRequestedSubjectIdValidator( $idValidator );
	}

}
