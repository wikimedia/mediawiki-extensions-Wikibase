<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\RemoveItemStatement;

use CommentStore;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RemoveItemStatementValidatorTest extends TestCase {

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	/**
	 * @dataProvider provideValidRequest
	 * @doesNotPerformAssertions
	 */
	public function testValidatePass( array $requestData ): void {
		$this->newRemoveItemStatementValidator()->assertValidRequest(
			$this->newUseCaseRequest( $requestData )
		);
	}

	/**
	 * @dataProvider provideInvalidRequest
	 */
	public function testValidateFails( array $requestData, string $errorCode, string $errorMessage ): void {
		try {
			$this->newRemoveItemStatementValidator()->assertValidRequest(
				$this->newUseCaseRequest( $requestData )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $errorCode, $e->getErrorCode() );
			$this->assertSame( $errorMessage, $e->getErrorMessage() );
		}
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
				'$itemId' => $itemId,
			],
		];
		yield 'Valid without item ID' => [
			[
				'$statementId' => $statementId,
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
			],
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
				'$itemId' => $itemId,
			],
			ItemIdValidator::CODE_INVALID,
			"Not a valid item ID: $itemId",
		];

		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'INVALID-STATEMENT-ID';
		yield 'Invalid statement ID (with item ID)' => [
			[
				'$statementId' => $statementId,
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
				'$itemId' => $itemId,
			],
			StatementIdValidator::CODE_INVALID,
			"Not a valid statement ID: $statementId",
		];
		yield 'Invalid statement ID (without item ID)' => [
			[
				'$statementId' => $statementId,
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
				'$itemId' => null,
			],
			StatementIdValidator::CODE_INVALID,
			"Not a valid statement ID: $statementId",
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
				'$itemId' => $itemId,
			],
			EditMetadataValidator::CODE_COMMENT_TOO_LONG,
			'Comment must not be longer than ' . CommentStore::COMMENT_CHARACTER_LIMIT . ' characters.',
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
				'$itemId' => null,
			],
			EditMetadataValidator::CODE_INVALID_TAG,
			"Invalid MediaWiki tag: \"$invalidTag\"",
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
