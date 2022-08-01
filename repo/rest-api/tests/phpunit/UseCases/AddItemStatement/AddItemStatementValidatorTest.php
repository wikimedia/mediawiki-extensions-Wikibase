<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\AddItemStatement;

use CommentStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\DataAccess\SnakValidatorStatementValidator;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementValidator;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddItemStatementValidatorTest extends TestCase {

	/**
	 * @var MockObject|SnakValidatorStatementValidator
	 */
	private $statementValidator;

	/**
	 * @var MockObject|EditMetadataValidator
	 */
	private $editMetadataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->statementValidator = $this->createStub( StatementValidator::class );
		$this->statementValidator->method( 'validate' )->willReturn( null );

		$this->editMetadataValidator = $this->createStub( EditMetadataValidator::class );
		$this->editMetadataValidator->method( 'validateEditTags' )->willReturn( null );
	}

	public function testValidatePass(): void {
		$error = $this->newAddItemStatementValidator()->validate(
			new AddItemStatementRequest( 'Q42', [ 'valid' => 'statement' ], [], false, null, null )
		);

		$this->assertNull( $error );
	}

	public function testWithInvalidItemId(): void {
		$itemId = 'X123';
		$error = $this->newAddItemStatementValidator()->validate(
			new AddItemStatementRequest( $itemId, [ 'valid' => 'statement' ], [], false, null, null )
		);

		$this->assertNotNull( $error );
		$this->assertSame( AddItemStatementValidator::SOURCE_ITEM_ID, $error->getSource() );
		$this->assertSame( $itemId, $error->getValue() );
	}

	public function testWithInvalidStatement(): void {
		$invalidStatement = [ 'this is' => 'not a valid statement' ];
		$expectedError = new ValidationError( '', AddItemStatementValidator::SOURCE_STATEMENT );
		$this->statementValidator = $this->createMock( StatementValidator::class );
		$this->statementValidator->method( 'validate' )
			->with( $invalidStatement, AddItemStatementValidator::SOURCE_STATEMENT )
			->willReturn( $expectedError );

		$error = $this->newAddItemStatementValidator()->validate(
			new AddItemStatementRequest( 'Q42', $invalidStatement, [], false, null, null )
		);

		$this->assertSame( $expectedError, $error );
	}

	public function testWithCommentTooLong(): void {
		$comment = str_repeat( 'x', CommentStore::COMMENT_CHARACTER_LIMIT + 1 );
		$expectedError = new ValidationError( "500", AddItemStatementValidator::SOURCE_COMMENT );

		$this->editMetadataValidator = $this->createMock( EditMetadataValidator::class );
		$this->editMetadataValidator->method( 'validateComment' )
			->with( $comment, AddItemStatementValidator::SOURCE_COMMENT )
			->willReturn( $expectedError );

		$error = $this->newAddItemStatementValidator()->validate(
			new AddItemStatementRequest( 'Q42', [ 'valid' => 'statement' ], [], false, $comment, null )
		);

		$this->assertSame( $expectedError, $error );
	}

	public function testWithInvalidEditTags(): void {
		$invalidTags = [ 'bad', 'tags' ];
		$expectedError = new ValidationError( json_encode( $invalidTags ), AddItemStatementValidator::SOURCE_EDIT_TAGS );

		$this->editMetadataValidator = $this->createMock( EditMetadataValidator::class );
		$this->editMetadataValidator->method( 'validateEditTags' )
			->with( $invalidTags, AddItemStatementValidator::SOURCE_EDIT_TAGS )
			->willReturn( $expectedError );

		$error = $this->newAddItemStatementValidator()->validate(
			new AddItemStatementRequest( 'Q42', [ 'valid' => 'statement' ], $invalidTags, false, null, null )
		);

		$this->assertSame( $expectedError, $error );
	}

	private function newAddItemStatementValidator(): AddItemStatementValidator {
		return new AddItemStatementValidator(
			new ItemIdValidator(),
			$this->statementValidator,
			$this->editMetadataValidator
		);
	}
}
