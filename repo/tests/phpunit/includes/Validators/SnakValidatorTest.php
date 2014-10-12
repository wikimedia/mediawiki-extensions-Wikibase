<?php

namespace Wikibase\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\DataValue;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use DataValues\UnknownValue;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\InMemoryDataTypeLookup;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Validators\SnakValidator;

/**
 * @covers Wikibase\Validators\SnakValidator
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseValidators
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SnakValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var DataTypeFactory
	 */
	protected $dataTypeFactory;

	/**
	 * @var PropertyDataTypeLookup
	 */
	protected $propertyDataTypeLookup;

	public function setUp() {
		parent::setUp();

		$numericValidator = new TestValidator( '/^[0-9]+$/' );
		$alphabeticValidator = new TestValidator( '/^[a-zA-Z]+$/' );
		$lengthValidator = new TestValidator( '/^.{1,10}$/' );

		$this->dataTypeFactory = new DataTypeFactory();
		$this->dataTypeFactory->registerDataType( new DataType( 'numeric', 'string', array( $numericValidator, $lengthValidator ) ) );
		$this->dataTypeFactory->registerDataType( new DataType( 'alphabetic', 'string', array( $alphabeticValidator, $lengthValidator ) ) );

		$p1 = new PropertyId( 'p1' );
		$p2 = new PropertyId( 'p2' );

		$this->propertyDataTypeLookup = new InMemoryDataTypeLookup();
		$this->propertyDataTypeLookup->setDataTypeForProperty( $p1, 'numeric' );
		$this->propertyDataTypeLookup->setDataTypeForProperty( $p2, 'alphabetic' );
	}

	public static function provideValidateClaimSnaks() {
		$p1 = new PropertyId( 'p1' ); // numeric
		$p2 = new PropertyId( 'p2' ); // alphabetic

		$cases = array();

		$claim = new Statement( new PropertyNoValueSnak( $p1 ) );
		$cases[] = array( $claim, 'empty claim', true );

		$claim = new Statement(
			new PropertyValueSnak( $p1, new StringValue( '12' ) )
		);
		$claim->setQualifiers( new SnakList( array(
			new PropertyValueSnak( $p2, new StringValue( 'abc' ) )
		) ) );
		$claim->setReferences( new ReferenceList( array (
			new Reference( new SnakList( array(
				new PropertyValueSnak( $p2, new StringValue( 'xyz' ) )
			) ) )
		) ) );
		$cases[] = array( $claim, 'conforming claim', true );

		$brokenClaim = clone $claim;
		$brokenClaim->setMainSnak(
			new PropertyValueSnak( $p1, new StringValue( 'kittens' ) )
		);
		$cases[] = array( $brokenClaim, 'error in main snak', false );

		$brokenClaim = clone $claim;
		$brokenClaim->setQualifiers( new SnakList( array(
			new PropertyValueSnak( $p2, new StringValue( '333' ) )
		) ) );
		$cases[] = array( $brokenClaim, 'error in qualifier', false );

		$brokenClaim = clone $claim;
		$brokenClaim->setReferences( new ReferenceList( array (
			new Reference( new SnakList( array(
				new PropertyValueSnak( $p1, new StringValue( 'xyz' ) )
			) ) )
		) ) );
		$cases[] = array( $brokenClaim, 'error in reference', false );

		return $cases;
	}

	/**
	 * @dataProvider provideValidateClaimSnaks
	 */
	public function testValidateClaimSnaks( Claim $claim, $description, $expectedValid = true ) {
		$validator = new SnakValidator( $this->propertyDataTypeLookup, $this->dataTypeFactory );

		$result = $validator->validateClaimSnaks( $claim );

		$this->assertEquals( $expectedValid, $result->isValid(), $description );
	}

	public static function provideValidateReferences() {
		$p1 = new PropertyId( 'p1' ); // numeric
		$p2 = new PropertyId( 'p2' ); // alphabetic

		$cases = array();

		$references = new ReferenceList();
		$cases[] = array( $references, 'empty reference list', true );

		$references = new ReferenceList( array (
			new Reference( new SnakList( array(
				new PropertyValueSnak( $p1, new StringValue( '123' ) )
			) ) ),
			new Reference( new SnakList( array(
				new PropertyValueSnak( $p2, new StringValue( 'abc' ) )
			) ) )
		) );
		$cases[] = array( $references, 'conforming reference list', true );

		$references = new ReferenceList( array (
			new Reference( new SnakList( array(
				new PropertyValueSnak( $p1, new StringValue( '123' ) )
			) ) ),
			new Reference( new SnakList( array(
				new PropertyValueSnak( $p2, new StringValue( '456' ) )
			) ) )
		) );
		$cases[] = array( $references, 'invalid reference list', false );

		return $cases;
	}

	/**
	 * @dataProvider provideValidateReferences
	 */
	public function testValidateReferences( ReferenceList $references, $description, $expectedValid = true ) {
		$validator = new SnakValidator( $this->propertyDataTypeLookup, $this->dataTypeFactory );

		$result = $validator->validateReferences( $references );

		$this->assertEquals( $expectedValid, $result->isValid(), $description );
	}


	public static function provideValidateReference() {
		$p1 = new PropertyId( 'p1' ); // numeric
		$p2 = new PropertyId( 'p2' ); // alphabetic

		$cases = array();

		$reference = new Reference( new SnakList() );
		$cases[] = array( $reference, 'empty reference', true );

		$reference = new Reference( new SnakList( array(
				new PropertyValueSnak( $p1, new StringValue( '123' ) ),
				new PropertyValueSnak( $p2, new StringValue( 'abc' ) )
			) )
		);
		$cases[] = array( $reference, 'conforming reference', true );

		$reference = new Reference( new SnakList( array(
				new PropertyValueSnak( $p1, new StringValue( '123' ) ),
				new PropertyValueSnak( $p2, new StringValue( '456' ) )
			) )
		);
		$cases[] = array( $reference, 'invalid reference', false );

		return $cases;
	}

	/**
	 * @dataProvider provideValidateReference
	 */
	public function testValidateReference( Reference $reference, $description, $expectedValid = true ) {
		$validator = new SnakValidator( $this->propertyDataTypeLookup, $this->dataTypeFactory );

		$result = $validator->validateReference( $reference );

		$this->assertEquals( $expectedValid, $result->isValid(), $description );
	}

	public static function provideValidate() {
		$p1 = new PropertyId( 'p1' ); // numeric
		$p2 = new PropertyId( 'p2' ); // alphabetic
		$p3 = new PropertyId( 'p3' ); // bad

		$cases = array();

		$snak = new PropertyNoValueSnak( $p1 );
		$cases[] = array( $snak, 'PropertyNoValueSnak', true );

		$snak = new PropertySomeValueSnak( $p2 );
		$cases[] = array( $snak, 'PropertySomeValueSnak', true );

		$snak = new PropertyValueSnak( $p1, new StringValue( '123' ) );
		$cases[] = array( $snak, 'valid numeric value', true );

		$snak = new PropertyValueSnak( $p2, new StringValue( '123' ) );
		$cases[] = array( $snak, 'invalid alphabetic value', false );

		$snak = new PropertyValueSnak( $p2, new StringValue( 'abc' ) );
		$cases[] = array( $snak, 'valid alphabetic value', true );

		$snak = new PropertyValueSnak( $p1, new StringValue( 'abc' ) );
		$cases[] = array( $snak, 'invalid numeric value', false );

		$snak = new PropertyValueSnak( $p1, new UnDeserializableValue( 'abc', 'string', 'error' ) );
		$cases[] = array( $snak, 'bad value', false );

		$snak = new PropertyValueSnak( $p1, new UnknownValue( 'abc' ) );
		$cases[] = array( $snak, 'wrong value type', false );

		$snak = new PropertyValueSnak( $p3, new StringValue( 'abc' ) );
		$cases[] = array( $snak, 'bad property', false );

		return $cases;
	}

	/**
	 * @dataProvider provideValidate
	 */
	public function testValidate( Snak $snak, $description, $expectedValid = true ) {
		$validator = new SnakValidator( $this->propertyDataTypeLookup, $this->dataTypeFactory );

		$result = $validator->validate( $snak );

		$this->assertEquals( $expectedValid, $result->isValid(), $description );
	}

	public static function provideValidateDataValue() {
		return array(
			array( new StringValue( '123' ), 'numeric', 'p1', 'valid numeric value', true ),
			array( new StringValue( '123' ), 'alphabetic', 'p2', 'invalid alphabetic value', false ),
			array( new StringValue( 'abc' ), 'alphabetic', 'p2', 'valid alphabetic value', true ),
			array( new StringValue( 'abc' ), 'numeric', 'p1', 'invalid numeric value', false ),
			array( new StringValue( '01234567890123456789' ), 'numeric', 'p1', 'overly long numeric value', false ),
			array( new UnknownValue( 'abc' ), 'alphabetic', 'p2', 'bad value type', false ),
			array( new UnDeserializableValue( 'abc', 'string', 'error' ), 'numeric', 'p1', 'bad value', false ),
		);
	}

	/**
	 * @dataProvider provideValidateDataValue
	 */
	public function testValidateDataValue( DataValue $dataValue, $dataTypeId, $propertyName, $description, $expectedValid = true ) {
		$validator = new SnakValidator( $this->propertyDataTypeLookup, $this->dataTypeFactory );

		$result = $validator->validateDataValue( $dataValue, $dataTypeId, $propertyName );

		$this->assertEquals( $expectedValid, $result->isValid(), $description );
	}

}
