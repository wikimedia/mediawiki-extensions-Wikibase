<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\ReplaceItemStatement;

use CommentStore;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\DataAccess\SnakValidatorStatementValidator;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementValidatorTest extends TestCase {

	/**
	 * @var MockObject|SnakValidatorStatementValidator
	 */
	private $statementValidator;

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	protected function setUp(): void {
		parent::setUp();

		$this->statementValidator = $this->createStub( StatementValidator::class );
		$this->statementValidator->method( 'validate' )->willReturn( null );
	}

	/**
	 * @dataProvider provideValidRequest
	 */
	public function testValidate_withValidRequest( array $requestData ): void {
		$error = $this->newReplaceItemStatementValidator()->validate(
			$this->newUseCaseRequest( $requestData )
		);

		$this->assertNull( $error );
	}

	public function provideValidRequest(): Generator {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		yield 'Valid with item ID' => [
			[
				'$statementId' => $statementId,
				'$statement' => [ 'valid' => 'statement' ],
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
				'$itemId' => $itemId
			]
		];
		yield 'Valid without item ID' => [
			[
				'$statementId' => $statementId,
				'$statement' => [ 'valid' => 'statement' ],
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null
			]
		];
	}

	public function testValidate_withInvalidItemId(): void {
		$itemId = 'X123';
		$error = $this->newReplaceItemStatementValidator()->validate(
			$this->newUseCaseRequest( [
				'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$statement' => [ 'valid' => 'statement' ],
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
				'$itemId' => $itemId
			] )
		);

		$this->assertNotNull( $error );
		$this->assertSame( ReplaceItemStatementValidator::SOURCE_ITEM_ID, $error->getSource() );
		$this->assertSame( $itemId, $error->getValue() );
	}

	public function testValidate_withInvalidStatementId(): void {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . "INVALID-STATEMENT-ID";
		$error = $this->newReplaceItemStatementValidator()->validate(
			$this->newUseCaseRequest( [
				'$statementId' => $statementId,
				'$statement' => [ 'valid' => 'statement' ],
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
				'$itemId' => $itemId
			] )
		);

		$this->assertNotNull( $error );
		$this->assertSame( ReplaceItemStatementValidator::SOURCE_STATEMENT_ID, $error->getSource() );
		$this->assertSame( $statementId, $error->getValue() );
	}

	public function testValidate_withInvalidStatement(): void {
		$invalidStatement = [ 'this is' => 'not a valid statement' ];
		$expectedError = new ValidationError( '', ReplaceItemStatementValidator::SOURCE_STATEMENT );
		$this->statementValidator = $this->createMock( StatementValidator::class );
		$this->statementValidator->method( 'validate' )
			->with( $invalidStatement, ReplaceItemStatementValidator::SOURCE_STATEMENT )
			->willReturn( $expectedError );

		$error = $this->newReplaceItemStatementValidator()->validate(
			$this->newUseCaseRequest( [
				'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$statement' => $invalidStatement,
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
				'$itemId' => null
			] )
		);

		$this->assertSame( $expectedError, $error );
	}

	public function testValidate_withChangedStatementId(): void {
		$error = $this->newReplaceItemStatementValidator()->validate(
			$this->newUseCaseRequest( [
				'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$statement' => [
					'id' => 'Q123$LLLLLLLL-MMMM-NNNN-OOOO-PPPPPPPPPPPP',
					'valid' => 'statement'
				],
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
				'$itemId' => null
			] )
		);

		$this->assertEquals(
			new ValidationError( '', ReplaceItemStatementValidator::SOURCE_CHANGED_STATEMENT_ID ),
			$error
		);
	}

	public function testValidate_withCommentTooLong(): void {
		$comment = str_repeat( 'x', CommentStore::COMMENT_CHARACTER_LIMIT + 1 );
		$error = $this->newReplaceItemStatementValidator()->validate(
			$this->newUseCaseRequest( [
				'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$statement' => [ 'valid' => 'statement' ],
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => $comment,
				'$username' => null,
				'$itemId' => null
			] )
		);

		$this->assertEquals(
			new ValidationError( "500", ReplaceItemStatementValidator::SOURCE_COMMENT ),
			$error
		);
	}

	public function testValidate_withInvalidEditTags(): void {
		$invalid = 'invalid';
		$error = $this->newReplaceItemStatementValidator()->validate(
			$this->newUseCaseRequest( [
				'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$statement' => [ 'valid' => 'statement' ],
				'$editTags' => [ 'some', 'tags', 'are', $invalid ],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
				'$itemId' => null
			] )
		);

		$this->assertEquals(
			new ValidationError( json_encode( $invalid ), ReplaceItemStatementValidator::SOURCE_EDIT_TAGS ),
			$error
		);
	}

	private function newReplaceItemStatementValidator(): ReplaceItemStatementValidator {
		return new ReplaceItemStatementValidator(
			new ItemIdValidator(),
			new StatementIdValidator( new ItemIdParser() ),
			$this->statementValidator,
			new EditMetadataValidator( CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
		);
	}

	private function newUseCaseRequest( array $requestData ): ReplaceItemStatementRequest {
		return new ReplaceItemStatementRequest(
			$requestData['$statementId'],
			$requestData['$statement'],
			$requestData['$editTags'],
			$requestData['$isBot'],
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null,
			$requestData['$itemId'] ?? null
		);
	}
}
