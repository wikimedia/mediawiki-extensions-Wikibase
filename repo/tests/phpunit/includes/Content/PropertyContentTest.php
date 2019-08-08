<?php

namespace Wikibase\Repo\Tests\Content;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use InvalidArgumentException;
use ParserOutput;
use Title;
use Wikibase\Content\EntityHolder;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\PropertyContent;

/**
 * @covers \Wikibase\PropertyContent
 * @covers \Wikibase\EntityContent
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseProperty
 * @group WikibaseContent
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyContentTest extends EntityContentTestCase {

	public function provideValidConstructorArguments() {
		return [
			'empty' => [ null ],
			'empty property' => [ new EntityInstanceHolder( Property::newFromType( 'string' ) ) ],
		];
	}

	/**
	 * @dataProvider provideValidConstructorArguments
	 */
	public function testConstructor( EntityHolder $holder = null ) {
		$content = new PropertyContent( $holder );
		$this->assertInstanceOf( PropertyContent::class, $content );
	}

	public function testConstructorExceptions() {
		$holder = new EntityInstanceHolder( new Item() );
		$this->setExpectedException( InvalidArgumentException::class );
		new PropertyContent( $holder );
	}

	/**
	 * @return PropertyId
	 */
	protected function getDummyId() {
		return new PropertyId( 'P100' );
	}

	/**
	 * @return string
	 */
	protected function getEntityType() {
		return Property::ENTITY_TYPE;
	}

	/**
	 * @return PropertyContent
	 */
	protected function newEmpty() {
		return new PropertyContent();
	}

	/**
	 * @param PropertyId|null $propertyId
	 *
	 * @throws InvalidArgumentException
	 * @return PropertyContent
	 */
	protected function newBlank( EntityId $propertyId = null ) {
		$property = Property::newFromType( 'string' );
		$property->setId( $propertyId );
		return new PropertyContent( new EntityInstanceHolder( $property ) );
	}

	public function provideGetEntityId() {
		$p11 = new PropertyId( 'P11' );

		return [
			'property id' => [ $this->newBlank( $p11 ), $p11 ],
		];
	}

	public function provideContentObjectsWithoutId() {
		return [
			'no holder' => [ new PropertyContent() ],
			'no ID' => [ new PropertyContent( new EntityInstanceHolder( Property::newFromType( 'string' ) ) ) ],
		];
	}

	public function testIsEmpty_emptyProperty() {
		$content = PropertyContent::newFromProperty( Property::newFromType( 'foo' ) );
		$this->assertTrue( $content->isEmpty() );
	}

	public function testIsEmpty_nonEmptyProperty() {
		$Property = Property::newFromType( 'foo' );
		$Property->setLabel( 'en', '~=[,,_,,]:3' );
		$content = PropertyContent::newFromProperty( $Property );
		$this->assertFalse( $content->isEmpty() );
	}

	public function testGetTextForFilters() {
		$property = new Property(
			new PropertyId( 'P123' ),
			new Fingerprint(
				new TermList( [ new Term( 'en', 'label1' ), new Term( 'de', 'label2' ) ] ),
				new TermList( [ new Term( 'en', 'descen' ), new Term( 'de', 'descde' ) ] ),
				new AliasGroupList(
					[
						new AliasGroup( 'fr', [ 'alias1', 'alias2' ] ),
						new AliasGroup( 'pt', [ 'alias3' ] ),
					]
				)
			),
			'dataTypeId',
			new StatementList(
				new Statement(
					new PropertyValueSnak(
						new PropertyId( 'P6654' ), new StringValue( 'stringvalue' )
					),
					new SnakList(
						[
							new PropertyValueSnak(
								new PropertyId( 'P6654' ),
								new GlobeCoordinateValue( new LatLongValue( 1, 2 ), 1 )
							),
							new PropertyValueSnak(
								new PropertyId( 'P6654' ),
								new TimeValue(
									'+2015-11-11T00:00:00Z',
									0,
									0,
									0,
									TimeValue::PRECISION_DAY,
									TimeValue::CALENDAR_GREGORIAN
								)
							),
						]
					),
					new ReferenceList(
						[
							new Reference(
								[
									new PropertySomeValueSnak( new PropertyId( 'P987' ) ),
									new PropertyNoValueSnak( new PropertyId( 'P986' ) )
								]
							)
						]
					),
					'imaguid'
				)
			)
		);

		$content = new PropertyContent( new EntityInstanceHolder( $property ) );
		$output = $content->getTextForFilters();

		$this->assertSame(
			trim( file_get_contents( __DIR__ . '/textForFiltersProperty.txt' ) ),
			$output
		);
	}

	public function testGetParserOutput() {
		$content = $this->newBlank();

		//@todo: Use a fake ID, no need to hit the database once we
		//       got rid of the rest of the storage logic.
		$this->entityStore->assignFreshId( $content->getEntity() );

		$title = Title::newFromText( 'Foo' );
		$parserOutput = $content->getParserOutput( $title );

		$expectedUsedOptions = [ 'userlang', 'wb', 'termboxVersion' ];
		$actualOptions = $parserOutput->getUsedOptions();
		$this->assertEquals(
			$expectedUsedOptions,
			$actualOptions,
			'Cache-split flags are not what they should be',
			0.0,
			1,
			true
		);

		$this->assertInstanceOf( ParserOutput::class, $parserOutput );
	}

}
