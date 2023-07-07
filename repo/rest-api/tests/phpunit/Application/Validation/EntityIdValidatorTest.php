<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Repo\RestApi\Application\Validation\EntityIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityIdValidatorTest extends TestCase {

	public function testWithInvalidId(): void {
		$invalidId = 'X123';

		$error = $this->newEntityIdValidator()->validate( $invalidId );

		$this->assertSame( EntityIdValidator::CODE_INVALID, $error->getCode() );
		$this->assertSame( $invalidId, $error->getContext()[EntityIdValidator::CONTEXT_VALUE] );
	}

	/**
	 * @dataProvider provideValidId
	 */
	public function testWithValidId( string $entityId ): void {
		$this->assertNull( $this->newEntityIdValidator()->validate( $entityId ) );
	}

	public function provideValidId(): Generator {
		yield 'item id' => [ 'Q123' ];
		yield 'property id' => [ 'P123' ];
	}

	private function newEntityIdValidator(): EntityIdValidator {
		return new EntityIdValidator( new BasicEntityIdParser() );
	}

}
