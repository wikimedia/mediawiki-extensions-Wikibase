<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Validators;

use MediaWikiIntegrationTestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Validators\EntityUriValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\EntityUriValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 */
class EntityUriValidatorTest extends MediaWikiIntegrationTestCase {

	public function testValidAnyEntityType(): void {
		$validator = new EntityUriValidator(
			$this->createConfiguredMock( EntityIdParser::class, [
				'parse' => new NumericPropertyId( 'P1' ),
			] ),
			'http://www.wikidata.org/entity/'
		);

		$result = $validator->validate( 'http://www.wikidata.org/entity/P1' );

		$this->assertTrue( $result->isValid() );
	}

	public function testValidItem(): void {
		$validator = new EntityUriValidator(
			$this->createConfiguredMock( EntityIdParser::class, [
				'parse' => new ItemId( 'Q1' ),
			] ),
			'https://example.org/entity/',
			'item'
		);

		$result = $validator->validate( 'https://example.org/entity/Q1' );

		$this->assertTrue( $result->isValid() );
	}

	private function assertError( Result $result ): void {
		$this->assertFalse( $result->isValid(), 'result must not be valid' );

		$localizer = new ValidatorErrorLocalizer();
		$msg = $localizer->getErrorMessage( $result->getErrors()[0] );
		$this->assertTrue( $msg->exists(), 'message must exist: ' . $msg->getKey() );
	}

	public function testBadPrefix(): void {
		$validator = new EntityUriValidator(
			$this->createStub( EntityIdParser::class ),
			'http://www.wikidata.org/entity/'
		);

		$result = $validator->validate( 'https://www.wikidata.org/wiki/Q1' );

		$this->assertError( $result );
	}

	public function testBadEntityId(): void {
		$entityIdParser = $this->createMock( EntityIdParser::class );
		$entityIdParser->expects( $this->once() )
			->method( 'parse' )
			->with( 'abc' )
			->willThrowException( new EntityIdParsingException() );
		$validator = new EntityUriValidator(
			$entityIdParser,
			'http://www.wikidata.org/entity/'
		);

		$result = $validator->validate( 'http://www.wikidata.org/entity/abc' );

		$this->assertError( $result );
	}

	/** @dataProvider provideUnnormalizedItemIdStrings */
	public function testUnnormalizedEntityId( string $itemIdString ): void {
		$entityIdParser = $this->createConfiguredMock( EntityIdParser::class, [
			'parse' => new ItemId( $itemIdString ) ] );
		$validator = new EntityUriValidator(
			$entityIdParser,
			'http://www.wikidata.org/entity/'
		);

		$result = $validator->validate( "http://www.wikidata.org/entity/$itemIdString" );

		$this->assertError( $result );
	}

	public function provideUnnormalizedItemIdStrings(): iterable {
		yield 'lowercase' => [ 'q1' ];
		yield 'empty repository name' => [ ':Q1' ];
	}

	public function testBadEntityType(): void {
		$validator = new EntityUriValidator(
			$this->createConfiguredMock( EntityIdParser::class, [
				'parse' => new NumericPropertyId( 'P1' ),
			] ),
			'https://example.org/entity/',
			'item'
		);

		$result = $validator->validate( 'https://example.org/entity/P1' );

		$this->assertError( $result );
	}

}
