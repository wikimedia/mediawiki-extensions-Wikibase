<?php

namespace Wikibase\Repo\Tests\Merge\Validator;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\Merge\Validator\NoCrossReferencingStatements;

/**
 * @covers \Wikibase\Repo\Merge\Validator\NoCrossReferencingStatements
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class NoCrossReferencingStatementsTest extends TestCase {

	/**
	 * @dataProvider provideSamples
	 */
	public function testValidate( $expected, $source, $target ) {
		$validator = new NoCrossReferencingStatements();

		$this->assertSame( $expected, $validator->validate( $source, $target ) );
	}

	public function provideSamples() {
		yield 'items with no statements' => [
			true,
			NewItem::withId( 'Q1' )->build(),
			NewItem::withId( 'Q2' )->build(),
		];

		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new ItemId( 'Q2' ) )
			->withSomeGuid()
			->build();

		yield 'items with no cross-reference in statements' => [
			true,
			NewItem::withId( 'Q66' )
				->andStatement( $statement )
				->build(),
			NewItem::withId( 'Q77' )
				->andStatement( $statement )
				->build(),
		];

		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new ItemId( 'Q2' ) )
			->withSomeGuid()
			->build();

		yield 'items with cross-reference in statement\'s main snak' => [
			false,
			NewItem::withId( 'Q1' )
				->andStatement( $statement )
				->build(),
			NewItem::withId( 'Q2' )->build(),
		];

		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new ItemId( 'Q1' ) )
			->withSomeGuid()
			->build();

		yield 'items with cross-reference in statement\'s main snak in opposite direction' => [
			false,
			NewItem::withId( 'Q1' )->build(),
			NewItem::withId( 'Q2' )
				->andStatement( $statement )
				->build(),
		];

		$statementWithReference = NewStatement::forProperty( 'P42' )
			->withValue( new ItemId( 'Q47' ) )
			->withSomeGuid()
			->build();
		$statementWithReference->addNewReference(
			NewStatement::forProperty( 'P48' )
				->withValue( new ItemId( 'Q2' ) )
				->withSomeGuid()
				->build()
				->getMainSnak()
		);

		yield 'item with cross-reference in statement\'s references' => [
			false,
			NewItem::withId( 'Q1' )
				->andStatement( $statementWithReference )
				->build(),
			NewItem::withId( 'Q2' )->build(),
		];

		$qualifiedStatement = NewStatement::forProperty( 'P42' )
			->withValue( new ItemId( 'Q47' ) )
			->withSomeGuid()
			->withQualifier( 'P48', new ItemId( 'Q2' ) )
			->build();

		yield 'item with cross-reference in statement\'s qualifiers' => [
			false,
			NewItem::withId( 'Q1' )
				->andStatement( $qualifiedStatement )
				->build(),
			NewItem::withId( 'Q2' )->build(),
		];
	}

	public function testViolatingPropertiesCanBeIdentified() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new ItemId( 'Q2' ) )
			->withSomeGuid()
			->build();

		$validator = new NoCrossReferencingStatements();
		$validator->validate(
			NewItem::withId( 'Q1' )
				->andStatement( $statement )
				->build(),
			NewItem::withId( 'Q2' )->build()
		);

		$this->assertEquals( [ new NumericPropertyId( 'P42' ) ], $validator->getViolations() );
	}

}
