<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Validation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Validation\StatementIdValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementIdValidatorTest extends TestCase {

	/**
	 * @dataProvider validItemStatementIdDataProvider
	 */
	public function testItemStatement_ValidId( string $statementId ): void {
		$this->assertNull(
			( new StatementIdValidator( new ItemIdParser() ) )->validate( $statementId )
		);
	}

	/**
	 * @dataProvider validItemStatementIdDataProvider
	 * @dataProvider validPropertyStatementIdDataProvider
	 */
	public function testBasicEntityStatement_ValidId( string $statementId ): void {
		$this->assertNull(
			( new StatementIdValidator( new BasicEntityIdParser() ) )->validate( $statementId )
		);
	}

	/**
	 * @dataProvider invalidStatementIdDataProvider
	 * @dataProvider invalidItemStatementIdDataProvider
	 */
	public function testItemStatement_InvalidId( string $invalidId ): void {
		$error = ( new StatementIdValidator( new ItemIdParser() ) )
			->validate( $invalidId );
		$this->assertSame( StatementIdValidator::CODE_INVALID, $error->getCode() );
		$this->assertSame( $invalidId, $error->getContext()[StatementIdValidator::CONTEXT_VALUE] );
	}

	/**
	 * @dataProvider invalidStatementIdDataProvider
	 */
	public function testBasicEntityStatement_InvalidId( string $invalidId ): void {
		$error = ( new StatementIdValidator( new BasicEntityIdParser() ) )->validate( $invalidId );

		$this->assertSame( StatementIdValidator::CODE_INVALID, $error->getCode() );
		$this->assertSame( $invalidId, $error->getContext()[StatementIdValidator::CONTEXT_VALUE] );
	}

	/**
	 * Valid data provider for StatementIdValidators that can parse ItemIds
	 */
	public function validItemStatementIdDataProvider(): Generator {
		yield 'valid with ItemId' => [ 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ];
	}

	/**
	 * Valid data provider for StatementIdValidators that can parse StatementIds
	 */
	public function validPropertyStatementIdDataProvider(): Generator {
		yield 'valid with ItemId' => [ 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ];
	}

	/**
	 * Invalid data provider for StatementIdValidators with any EntityIdParser
	 */
	public function invalidStatementIdDataProvider(): Generator {
		yield 'invalid format' => [ 'not-a-valid-statement-id' ];
		yield 'invalid EntityId' => [ 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ];
		yield 'invalid UUID part (with ItemId)' => [ 'Q123$INVALID-UUID-PART' ];
		yield 'invalid UUID part (with PropertyId)' => [ 'P123$INVALID-UUID-PART' ];
	}

	/**
	 * Invalid data provider for StatementIdValidators with ItemIdParser
	 */
	public function invalidItemStatementIdDataProvider(): Generator {
		yield 'statement not on an item' => [ 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ];
	}

}
