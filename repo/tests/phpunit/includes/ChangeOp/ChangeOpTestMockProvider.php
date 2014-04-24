<?php

namespace Wikibase\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\DataValue;
use DataValues\NumberValue;
use DataValues\StringValue;
use OutOfBoundsException;
use PHPUnit_Framework_MockObject_MockBuilder;
use PHPUnit_Framework_TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\SiteLinkCache;
use Wikibase\Validators\CompositeValidator;
use Wikibase\Validators\DataValueValidator;
use Wikibase\Validators\RegexValidator;
use Wikibase\Validators\SnakValidator;
use Wikibase\Validators\TypeValidator;

/**
 * A helper class for test cases that deal with claims.
 * Provides mock services frequently used with claims.
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeOpTestMockProvider {

	/**
	 * @var
	 */
	private $mockBuilderFactory;

	/**
	 * @param PHPUnit_Framework_TestCase $mockBuilderFactory
	 */
	public function __construct( PHPUnit_Framework_TestCase $mockBuilderFactory ) {
		$this->mockBuilderFactory = $mockBuilderFactory;
	}

	/**
	 * @see PHPUnit_Framework_TestCase::getMockBuilder
	 *
	 * @param string $class
	 *
	 * @return PHPUnit_Framework_MockObject_MockBuilder
	 */
	protected function getMockBuilder( $class ) {
		return $this->mockBuilderFactory->getMockBuilder( $class );
	}

	/**
	 * @see PHPUnit_Framework_TestCase::getMock
	 *
	 * @param string $class
	 *
	 * @return object
	 */
	protected function getMock( $class ) {
		return $this->mockBuilderFactory->getMock( $class );
	}

	/**
	 * Convenience method for creating Statements.
	 *
	 * @param string|PropertyId $propertyId
	 *
	 * @param string|int|float|DataValue|null $value The value of the new
	 *        claim's main snak. Null will result in a PropertyNoValueSnak.
	 *
	 * @return Statement A new statement with a main snak based on the parameters provided.
	 */
	public function makeStatement( $propertyId, $value = null ) {
		if ( is_string( $value ) ) {
			$value = new StringValue( $value );
		} elseif ( is_int( $value ) || is_float( $value ) ) {
			$value = new NumberValue( $value );
		}

		if ( is_string( $propertyId ) ) {
			$propertyId = new PropertyId( $propertyId );
		}

		if ( $value === null ) {
			$snak = new PropertyNoValueSnak( $propertyId );
		} else {
			$snak = new PropertyValueSnak( $propertyId, $value );
		}

		return new Statement( $snak );
	}

	/**
	 * Returns a normal ClaimGuidGenerator.
	 *
	 * @return ClaimGuidGenerator
	 */
	public function getGuidGenerator() {
		return new ClaimGuidGenerator();
	}

	/**
	 * Returns a mock ClaimGuidValidator that accepts any GUID.
	 *
	 * @return ClaimGuidValidator
	 */
	public function getMockGuidValidator() {
		$mock = $this->getMockBuilder( '\Wikibase\Lib\ClaimGuidValidator' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'validate' )
			->will( PHPUnit_Framework_TestCase::returnValue( true ) );
		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'validateFormat' )
			->will( PHPUnit_Framework_TestCase::returnValue( true ) );
		return $mock;
	}

	/**
	 * Returns a mock SnakValidator based on getMockPropertyDataTypeLookup()
	 * and getMockDataTypeFactory(), which will accept snaks containing a StringValue
	 * that is not "INVALID".
	 *
	 * @return SnakValidator
	 */
	public function getMockSnakValidator() {
		return new SnakValidator(
			$this->getMockPropertyDataTypeLookup(),
			$this->getMockDataTypeFactory()
		);
	}

	/**
	 * Returns a mock PropertyDataTypeLookup that will return the
	 * type id "string" for any property.
	 *
	 * @return PropertyDataTypeLookup
	 */
	public function getMockPropertyDataTypeLookup() {
		$mock = $this->getMock( '\Wikibase\Lib\PropertyDataTypeLookup' );
		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'getDataTypeIdForProperty' )
			->will( PHPUnit_Framework_TestCase::returnValue( 'string' ) );

		return $mock;
	}

	/**
	 * Returns a mock MockDataTypeFactory that will return the same DataType for
	 * any type id; The ValueValidators of that DataType will accept any
	 * StringValue, unless the string is "INVALID".
	 *
	 * @return DataTypeFactory
	 */
	public function getMockDataTypeFactory() {
		// consider "INVALID" to be invalid
		$topValidator = new DataValueValidator(
			new CompositeValidator( array(
				new TypeValidator( 'string' ),
				new RegexValidator( '/INVALID/', true ),
			), true )
		);

		$validators = array( new TypeValidator( 'DataValues\DataValue' ), $topValidator );
		$stringType = new DataType( 'string', 'string', $validators );

		$types = array(
			'string' => $stringType
		);

		$mock = $this->getMockBuilder( 'DataTypes\DataTypeFactory' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'getType' )
			->will( PHPUnit_Framework_TestCase::returnCallback( function ( $id ) use ( $types ) {
				if ( !isset( $types[$id] ) ) {
					throw new OutOfBoundsException( "No such type: $id" );
				}

				return $types[$id];
			} ) );

		return $mock;
	}

	/**
	 * Returns a mock ClaimGuidParser that will return the same ClaimGuid for
	 * all input strings.
	 *
	 * @param EntityId $entityId
	 *
	 * @return ClaimGuidParser
	 */
	public function getMockGuidParser( EntityId $entityId ) {
		$mockClaimGuid = $this->getMockBuilder( 'Wikibase\DataModel\Claim\ClaimGuid' )
			->disableOriginalConstructor()
			->getMock();
		$mockClaimGuid->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'getSerialization' )
			->will( PHPUnit_Framework_TestCase::returnValue( 'theValidatorIsMockedSoMeh! :D' ) );
		$mockClaimGuid->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'getEntityId' )
			->will( PHPUnit_Framework_TestCase::returnValue( $entityId ) );

		$mock = $this->getMockBuilder( 'Wikibase\DataModel\Claim\ClaimGuidParser' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'parse' )
			->will( PHPUnit_Framework_TestCase::returnValue( $mockClaimGuid ) );
		return $mock;
	}


	/**
	 * @param null $returnValue
	 *
	 * @return LabelDescriptionDuplicateDetector
	 */
	public function getMockLabelDescriptionDuplicateDetector( $returnValue = null ) {
		if ( $returnValue === null ) {
			$returnValue = Result::newSuccess();
		} elseif ( is_array( $returnValue ) ) {
			$returnValue = Result::newError( $returnValue );
		}

		$mock = $this->getMockBuilder( '\Wikibase\LabelDescriptionDuplicateDetector' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'detectLabelConflictsForEntity' )
			->will( PHPUnit_Framework_TestCase::returnValue( $returnValue ) );
		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'detectLabelDescriptionConflictsForEntity' )
			->will( PHPUnit_Framework_TestCase::returnValue( $returnValue ) );
		return $mock;
	}

	/**
	 * @param array $returnValue
	 *
	 * @return SiteLinkCache
	 */
	public function getMockSitelinkCache( $returnValue = array() ) {
		$mock = $this->getMock( '\Wikibase\SiteLinkCache' );
		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'getConflictsForItem' )
			->will( PHPUnit_Framework_TestCase::returnValue( $returnValue ) );
		return $mock;
	}

	/**
	 * @return ClaimGuidGenerator
	 */
	public function getMockGuidGenerator() {
		return new ClaimGuidGenerator();
	}
}
