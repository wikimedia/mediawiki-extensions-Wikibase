<?php

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\Lib\DataTypeFactory;
use DataValues\DataValue;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use DataValues\UnknownValue;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use ValueValidators\Error;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\DataTypeValidatorFactory;
use Wikibase\Repo\Validators\SnakValidator;

/**
 * @covers Wikibase\Repo\Validators\SnakValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SnakValidatorTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var DataTypeFactory
	 */
	protected $dataTypeFactory;

	/**
	 * @var PropertyDataTypeLookup
	 */
	protected $propertyDataTypeLookup;

	/**
	 * @var DataTypeValidatorFactory
	 */
	private $validatorFactory;

	protected function setUp() {
		parent::setUp();

		$numericValidator = new TestValidator( '/^\d+$/' );
		$alphabeticValidator = new TestValidator( '/^[A-Z]+$/i' );
		$lengthValidator = new TestValidator( '/^.{1,10}$/' );

		$this->dataTypeFactory = new DataTypeFactory( [
			'numeric' => 'string',
			'alphabetic' => 'string'
		] );

		$p1 = new PropertyId( 'P1' );
		$p2 = new PropertyId( 'P2' );
		$p4 = new PropertyId( 'P4' );

		$this->propertyDataTypeLookup = new InMemoryDataTypeLookup();
		$this->propertyDataTypeLookup->setDataTypeForProperty( $p1, 'numeric' );
		$this->propertyDataTypeLookup->setDataTypeForProperty( $p2, 'alphabetic' );
		$this->propertyDataTypeLookup->setDataTypeForProperty( $p4, 'fiddlediddle' );

		$this->validatorFactory = $this->getMock( DataTypeValidatorFactory::class );
		$this->validatorFactory->expects( $this->any() )
			->method( 'getValidators' )
			->will( $this->returnCallback( function( $dataTypeId ) use (
				$numericValidator,
				$alphabeticValidator,
				$lengthValidator
			) {
				return [
					$dataTypeId === 'numeric' ? $numericValidator : $alphabeticValidator,
					$lengthValidator
				];
			} ) );
	}

	public function provideValidateClaimSnaks() {
		$p1 = new PropertyId( 'p1' ); // numeric
		$p2 = new PropertyId( 'p2' ); // alphabetic

		$cases = [];

		$claim = new Statement( new PropertyNoValueSnak( $p1 ) );
		$cases[] = [ $claim, 'empty claim', true ];

		$claim = new Statement(
			new PropertyValueSnak( $p1, new StringValue( '12' ) )
		);
		$claim->setQualifiers( new SnakList( [
			new PropertyValueSnak( $p2, new StringValue( 'abc' ) )
		] ) );
		$claim->setReferences( new ReferenceList( [
			new Reference( new SnakList( [
				new PropertyValueSnak( $p2, new StringValue( 'xyz' ) )
			] ) )
		] ) );
		$cases[] = [ $claim, 'conforming claim', true ];

		$brokenClaim = clone $claim;
		$brokenClaim->setMainSnak(
			new PropertyValueSnak( $p1, new StringValue( 'kittens' ) )
		);
		$cases[] = [ $brokenClaim, 'error in main snak', false ];

		$brokenClaim = clone $claim;
		$brokenClaim->setQualifiers( new SnakList( [
			new PropertyValueSnak( $p2, new StringValue( '333' ) )
		] ) );
		$cases[] = [ $brokenClaim, 'error in qualifier', false ];

		$brokenClaim = clone $claim;
		$brokenClaim->setReferences( new ReferenceList( [
			new Reference( new SnakList( [
				new PropertyValueSnak( $p1, new StringValue( 'xyz' ) )
			] ) )
		] ) );
		$cases[] = [ $brokenClaim, 'error in reference', false ];

		return $cases;
	}

	/**
	 * @dataProvider provideValidateClaimSnaks
	 */
	public function testValidateClaimSnaks( Statement $statement, $description, $expectedValid = true ) {
		$validator = $this->getSnakValidator();

		$result = $validator->validateClaimSnaks( $statement );

		$this->assertEquals( $expectedValid, $result->isValid(), $description );
	}

	public function provideValidateReferences() {
		$p1 = new PropertyId( 'p1' ); // numeric
		$p2 = new PropertyId( 'p2' ); // alphabetic

		$cases = [];

		$references = new ReferenceList();
		$cases[] = [ $references, 'empty reference list', true ];

		$references = new ReferenceList( [
			new Reference( new SnakList( [
				new PropertyValueSnak( $p1, new StringValue( '123' ) )
			] ) ),
			new Reference( new SnakList( [
				new PropertyValueSnak( $p2, new StringValue( 'abc' ) )
			] ) )
		] );
		$cases[] = [ $references, 'conforming reference list', true ];

		$references = new ReferenceList( [
			new Reference( new SnakList( [
				new PropertyValueSnak( $p1, new StringValue( '123' ) )
			] ) ),
			new Reference( new SnakList( [
				new PropertyValueSnak( $p2, new StringValue( '456' ) )
			] ) )
		] );
		$cases[] = [ $references, 'invalid reference list', false ];

		return $cases;
	}

	/**
	 * @dataProvider provideValidateReferences
	 */
	public function testValidateReferences( ReferenceList $references, $description, $expectedValid = true ) {
		$validator = $this->getSnakValidator();

		$result = $validator->validateReferences( $references );

		$this->assertEquals( $expectedValid, $result->isValid(), $description );
	}

	public function provideValidateReference() {
		$p1 = new PropertyId( 'p1' ); // numeric
		$p2 = new PropertyId( 'p2' ); // alphabetic

		$cases = [];

		$reference = new Reference( new SnakList() );
		$cases[] = [ $reference, 'empty reference', true ];

		$reference = new Reference( new SnakList( [
				new PropertyValueSnak( $p1, new StringValue( '123' ) ),
				new PropertyValueSnak( $p2, new StringValue( 'abc' ) )
			] )
		);
		$cases[] = [ $reference, 'conforming reference', true ];

		$reference = new Reference( new SnakList( [
				new PropertyValueSnak( $p1, new StringValue( '123' ) ),
				new PropertyValueSnak( $p2, new StringValue( '456' ) )
			] )
		);
		$cases[] = [ $reference, 'invalid reference', false ];

		return $cases;
	}

	private function getSnakValidator() {
		return new SnakValidator(
			$this->propertyDataTypeLookup, $this->dataTypeFactory, $this->validatorFactory
		);
	}

	/**
	 * @dataProvider provideValidateReference
	 */
	public function testValidateReference( Reference $reference, $description, $expectedValid = true ) {
		$validator = $this->getSnakValidator();

		$result = $validator->validateReference( $reference );

		$this->assertEquals( $expectedValid, $result->isValid(), $description );
	}

	public function testGivenNonSnak_validateFails() {
		$validator = $this->getSnakValidator();
		$this->setExpectedException( InvalidArgumentException::class );
		$validator->validate( null );
	}

	public function provideValidate() {
		$p1 = new PropertyId( 'P1' ); // numeric
		$p2 = new PropertyId( 'P2' ); // alphabetic
		$p3 = new PropertyId( 'P3' ); // bad
		$p4 = new PropertyId( 'P4' ); // property with bad data type

		$cases = [];

		$snak = new PropertyNoValueSnak( $p1 );
		$cases[] = [ $snak, 'PropertyNoValueSnak' ];

		$snak = new PropertySomeValueSnak( $p2 );
		$cases[] = [ $snak, 'PropertySomeValueSnak' ];

		$snak = new PropertyValueSnak( $p1, new StringValue( '123' ) );
		$cases[] = [ $snak, 'valid numeric value' ];

		$snak = new PropertyValueSnak( $p2, new StringValue( 'abc' ) );
		$cases[] = [ $snak, 'valid alphabetic value' ];

		$snak = new PropertyValueSnak( $p2, new StringValue( '123' ) );
		$cases[] = [
			$snak,
			'invalid alphabetic value',
			Error::newError(
				'doesn\'t match /^[A-Z]+$/i',
				null,
				'invalid',
				[]
			)
		];

		$snak = new PropertyValueSnak( $p1, new StringValue( 'abc' ) );
		$cases[] = [
			$snak,
			'invalid numeric value',
			Error::newError(
				'doesn\'t match /^\d+$/',
				null,
				'invalid',
				[]
			)
		];

		$snak = new PropertyValueSnak( $p1, new UnDeserializableValue( 'abc', 'string', 'ooops' ) );
		$cases[] = [
			$snak,
			'bad value',
			Error::newError(
				'Bad snak value: ooops',
				null,
				'bad-value',
				[ 'ooops' ]
			)
		];

		$snak = new PropertyValueSnak( $p1, new UnknownValue( 'abc' ) );
		$cases[] = [
			$snak,
			'wrong value type',
			Error::newError(
				'Bad value type: unknown, expected string',
				null,
				'bad-value-type',
				[ 'unknown', 'string' ]
			)
		];

		$snak = new PropertyValueSnak( $p3, new StringValue( 'abc' ) );
		$cases[] = [
			$snak,
			'bad property',
			Error::newError(
				'Property P3 not found!',
				null,
				'no-such-property',
				[ 'P3' ]
			)
		];

		$snak = new PropertyValueSnak( $p4, new StringValue( 'abc' ) );
		$cases[] = [
			$snak,
			'bad data type',
			Error::newError(
				'Bad data type: fiddlediddle',
				null,
				'bad-data-type',
				[ 'fiddlediddle' ]
			)
		];

		return $cases;
	}

	/**
	 * @dataProvider provideValidate
	 */
	public function testValidate( Snak $snak, $description, $expectedError = null ) {
		$validator = $this->getSnakValidator();

		$result = $validator->validate( $snak );

		if ( $expectedError === null ) {
			$this->assertTrue( $result->isValid(), $description . ': isValid' );
		} else {
			$resultErrors = $result->getErrors();
			$firstError = reset( $resultErrors );
			$this->assertEquals( $expectedError, $firstError, $description );
		}
	}

	public function provideValidateDataValue() {
		return [
			[ new StringValue( '123' ), 'numeric', 'valid numeric value', true ],
			[ new StringValue( '123' ), 'alphabetic', 'invalid alphabetic value', false ],
			[ new StringValue( 'abc' ), 'alphabetic', 'valid alphabetic value', true ],
			[ new StringValue( 'abc' ), 'numeric', 'invalid numeric value', false ],
			[ new StringValue( '01234567890123456789' ), 'numeric', 'overly long numeric value', false ],
			[ new UnknownValue( 'abc' ), 'alphabetic', 'bad value type', false ],
			[ new UnDeserializableValue( 'abc', 'string', 'error' ), 'numeric', 'bad value', false ],
		];
	}

	/**
	 * @dataProvider provideValidateDataValue
	 */
	public function testValidateDataValue( DataValue $dataValue, $dataTypeId, $description, $expectedValid = true ) {
		$validator = $this->getSnakValidator();

		$result = $validator->validateDataValue( $dataValue, $dataTypeId );

		$this->assertEquals( $expectedValid, $result->isValid(), $description );
	}

}
