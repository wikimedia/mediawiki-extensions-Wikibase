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
	public function testValidateFails( array $requestData, string $errorValue, string $errorSource ): void {
		$error = $this->newRemoveItemStatementValidator()->validate(
			$this->newUseCaseRequest( $requestData )
		);

		$this->assertNotNull( $error );
		$this->assertSame( $errorValue, $error->getValue() );
		$this->assertSame( $errorSource, $error->getSource() );
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
			$itemId,
			RemoveItemStatementValidator::SOURCE_ITEM_ID
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
			$statementId,
			RemoveItemStatementValidator::SOURCE_STATEMENT_ID
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
			$statementId,
			RemoveItemStatementValidator::SOURCE_STATEMENT_ID
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
			strval( CommentStore::COMMENT_CHARACTER_LIMIT ),
			RemoveItemStatementValidator::SOURCE_COMMENT
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
			json_encode( $invalidTag ),
			RemoveItemStatementValidator::SOURCE_EDIT_TAGS
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
