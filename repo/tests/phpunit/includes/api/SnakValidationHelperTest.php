<?php

namespace Wikibase\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\StringValue;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\ReferenceList;
use Wikibase\Snak;
use Wikibase\SnakList;
use Wikibase\Statement;
use Wikibase\Validators\SnakValidator;

/**
 * @covers Wikibase\Api\SnakValidationHelper
 *
 * @group Wikibase
 * @group WikibaseValidators
 * @group WikibaseAPI
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SnakValidationHelperTest extends \PHPUnit_Framework_TestCase {

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
		$this->dataTypeFactory->registerDataType(
			new DataType( 'numeric', 'string', array( $numericValidator, $lengthValidator ) )
		);

		$this->dataTypeFactory->registerDataType(
			new DataType( 'alphabetic', 'string', array( $alphabeticValidator, $lengthValidator ) )
		);

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

	public static function provideValidateSnak() {
		//TODO: share code with SnakValidatorTest

		$p1 = new PropertyId( 'p1' ); // numeric
		$p2 = new PropertyId( 'p2' ); // alphabetic

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

		return $cases;
	}

	/**
	 * @dataProvider provideValidateSnak
	 */
	public function testValidateSnak( Snak $snak, $description, $expectedValid = true ) {
		$validator = new SnakValidator( $this->propertyDataTypeLookup, $this->dataTypeFactory );

		$result = $validator->validate( $snak );

		$this->assertEquals( $expectedValid, $result->isValid(), $description );
	}

}

