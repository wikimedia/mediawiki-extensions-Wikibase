<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\RemoveItemStatement;

use CommentStore;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatementValidator;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\AddItemStatement\RemoveItemStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RemoveItemStatementValidatorTest extends TestCase {

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	/**
	 * @dataProvider provideValidRequest
	 */
	public function testValidatePass( array $requestData ): void {
		$error = $this->newRemoveItemStatementValidator()->validate(
			$this->newUseCaseRequest( $requestData )
		);

		$this->assertNull( $error );
	}

	/**
	 * @dataProvider provideInvalidRequest
	 */
	public function testValidateFails( array $requestData, array $errorContext, string $errorCode ): void {
		$error = $this->newRemoveItemStatementValidator()->validate(
			$this->newUseCaseRequest( $requestData )
		);

		$this->assertNotNull( $error );
		$this->assertSame( $errorContext, $error->getContext() );
		$this->assertSame( $errorCode, $error->getCode() );
	}

	public function provideValidRequest(): Generator {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		yield 'Valid with item ID' => [
			[
				'$statementId' => $statementId,
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
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null
			]
		];
	}

	public function provideInvalidRequest(): Generator {
		$itemId = 'Z2Z';
		yield 'Invalid item ID' => [
			[
				'$statementId' => $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
				'$itemId' => $itemId
			],
			[ ItemIdValidator::ERROR_CONTEXT_VALUE => $itemId ],
			ItemIdValidator::CODE_INVALID
		];

		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . "INVALID-STATEMENT-ID";
		yield 'Invalid statement ID (with item ID)' => [
			[
				'$statementId' => $statementId,
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
				'$itemId' => $itemId
			],
			[ StatementIdValidator::ERROR_CONTEXT_VALUE => $statementId ],
			StatementIdValidator::CODE_INVALID
		];
		yield 'Invalid statement ID (without item ID)' => [
			[
				'$statementId' => $statementId,
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
				'$itemId' => null
			],
			[ StatementIdValidator::ERROR_CONTEXT_VALUE => $statementId ],
			StatementIdValidator::CODE_INVALID
		];

		$itemId = 'Q42';
		$comment = str_repeat( 'x', CommentStore::COMMENT_CHARACTER_LIMIT + 1 );
		yield 'Comment too long' => [
			[
				'$statementId' => $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => $comment,
				'$username' => null,
				'$itemId' => $itemId
			],
			[ EditMetadataValidator::ERROR_CONTEXT_COMMENT_MAX_LENGTH => strval( CommentStore::COMMENT_CHARACTER_LIMIT ) ],
			EditMetadataValidator::CODE_COMMENT_TOO_LONG
		];

		$itemId = 'Q24';
		$invalidTag = 'invalid';
		yield 'Invalid edit tags' => [
			[
				'$statementId' => $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$editTags' => [ 'some', 'tags', 'are', $invalidTag ],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
				'$itemId' => null
			],
			[ EditMetadataValidator::ERROR_CONTEXT_TAG_VALUE => json_encode( $invalidTag ) ],
			EditMetadataValidator::CODE_INVALID_TAG
		];
	}

	private function newRemoveItemStatementValidator(): RemoveItemStatementValidator {
		return new RemoveItemStatementValidator(
			new ItemIdValidator(),
			new StatementIdValidator( new ItemIdParser() ),
			new EditMetadataValidator( CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
		);
	}

	private function newUseCaseRequest( array $requestData ): RemoveItemStatementRequest {
		return new RemoveItemStatementRequest(
			$requestData['$statementId'],
			$requestData['$editTags'],
			$requestData['$isBot'],
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null,
			$requestData['$itemId'] ?? null
		);
	}
}
