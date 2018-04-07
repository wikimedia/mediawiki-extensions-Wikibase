<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use Wikibase\Lib\DataType;
use Wikibase\Lib\DataTypeFactory;
use DataValues\DataValue;
use DataValues\NumberValue;
use DataValues\StringValue;
use OutOfBoundsException;
use PHPUnit_Framework_MockObject_MockBuilder;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit\Framework\TestCase;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Repo\DataTypeValidatorFactory;
use Wikibase\Repo\Store\SiteLinkConflictLookup;
use Wikibase\Repo\Validators\NullFingerprintValidator;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\DataValueValidator;
use Wikibase\Repo\Validators\LabelDescriptionUniquenessValidator;
use Wikibase\Repo\Validators\RegexValidator;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\Validators\TypeValidator;

/**
 * A helper class for test cases that deal with claims.
 * Provides mock services frequently used with claims.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangeOpTestMockProvider {

	/**
	 * @var TestCase
	 */
	private $mockBuilderFactory;

	public function __construct( TestCase $mockBuilderFactory ) {
		$this->mockBuilderFactory = $mockBuilderFactory;
	}

	/**
	 * @see TestCase::getMockBuilder
	 *
	 * @param string $class
	 *
	 * @return PHPUnit_Framework_MockObject_MockBuilder
	 */
	private function getMockBuilder( $class ) {
		return $this->mockBuilderFactory->getMockBuilder( $class );
	}

	/**
	 * @see TestCase::getMock
	 *
	 * @param string $class
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMock( $class ) {
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
	 * Returns a mock StatementGuidValidator that accepts any GUID.
	 *
	 * @return StatementGuidValidator
	 */
	public function getMockGuidValidator() {
		$mock = $this->getMockBuilder( StatementGuidValidator::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( TestCase::any() )
			->method( 'validate' )
			->will( TestCase::returnValue( true ) );
		$mock->expects( TestCase::any() )
			->method( 'validateFormat' )
			->will( TestCase::returnValue( true ) );
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
			$this->getMockDataTypeFactory(),
			$this->getMockDataTypeValidatorFactory()
		);
	}

	/**
	 * Returns a mock PropertyDataTypeLookup that will return the
	 * type id "string" for any property.
	 *
	 * @return PropertyDataTypeLookup
	 */
	public function getMockPropertyDataTypeLookup() {
		$mock = $this->getMock( PropertyDataTypeLookup::class );
		$mock->expects( TestCase::any() )
			->method( 'getDataTypeIdForProperty' )
			->will( TestCase::returnValue( 'string' ) );

		return $mock;
	}

	/**
	 * Returns a mock MockDataTypeFactory that will return the same DataType for
	 * any type id.
	 *
	 * @return DataTypeFactory
	 */
	public function getMockDataTypeFactory() {
		$stringType = new DataType( 'string', 'string' );

		$types = [
			'string' => $stringType
		];

		$mock = $this->getMockBuilder( DataTypeFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( TestCase::any() )
			->method( 'getType' )
			->will( TestCase::returnCallback( function( $id ) use ( $types ) {
				if ( !isset( $types[$id] ) ) {
					throw new OutOfBoundsException( "No such type: $id" );
				}

				return $types[$id];
			} ) );

		return $mock;
	}

	/**
	 * Returns a mock DataTypeValidatorFactory that returns validators which will accept any
	 * StringValue, unless the string is "INVALID".
	 *
	 * @return DataTypeValidatorFactory
	 */
	public function getMockDataTypeValidatorFactory() {
		// consider "INVALID" to be invalid
		$topValidator = new DataValueValidator(
			new CompositeValidator( [
				new TypeValidator( 'string' ),
				new RegexValidator( '/INVALID/', true ),
			], true )
		);

		$validators = [ new TypeValidator( DataValue::class ), $topValidator ];

		$mock = $this->getMock( DataTypeValidatorFactory::class );
		$mock->expects( TestCase::any() )
			->method( 'getValidators' )
			->will( TestCase::returnCallback( function( $id ) use ( $validators ) {
				return $validators;
			} ) );

		return $mock;
	}

	/**
	 * Returns a mock validator. The term and the language "INVALID" is considered to be
	 * invalid.
	 *
	 * @return ValueValidator
	 */
	public function getMockTermValidator() {
		$mock = $this->getMock( ValueValidator::class );
		$mock->expects( TestCase::any() )
			->method( 'validate' )
			->will( TestCase::returnCallback( function( $text ) {
				if ( $text === 'INVALID' ) {
					$error = Error::newError( 'Invalid', '', 'test-invalid' );
					return Result::newError( [ $error ] );
				} else {
					return Result::newSuccess();
				}
			} ) );

		return $mock;
	}

	/**
	 * Returns a mock StatementGuidParser that will return the same ClaimGuid for
	 * all input strings.
	 *
	 * @param EntityId $entityId
	 *
	 * @return StatementGuidParser
	 */
	public function getMockGuidParser( EntityId $entityId ) {
		$guid = $this->getMockBuilder( StatementGuid::class )
			->disableOriginalConstructor()
			->getMock();
		$guid->expects( TestCase::any() )
			->method( 'getSerialization' )
			->will( TestCase::returnValue( 'theValidatorIsMockedSoMeh! :D' ) );
		$guid->expects( TestCase::any() )
			->method( 'getEntityId' )
			->will( TestCase::returnValue( $entityId ) );

		$mock = $this->getMockBuilder( StatementGuidParser::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( TestCase::any() )
			->method( 'parse' )
			->will( TestCase::returnValue( $guid ) );
		return $mock;
	}

	public function detectLabelConflicts(
		$entityType,
		array $labels,
		array $aliases = null,
		EntityId $entityId = null
	) {
		if ( $entityId && $entityId->getSerialization() === 'P666' ) {
			// simulated conflicts always conflict with P666, so if these are
			// ignored as self-conflicts, we don't need to check any labels.
			$labels = [];
		}

		foreach ( $labels as $lang => $text ) {
			if ( $text === 'DUPE' ) {
				return Result::newError( [
					Error::newError(
						'found conflicting terms',
						'label',
						'label-conflict',
						[
							'label',
							$lang,
							$text,
							'P666'
						]
					)
				] );
			}
		}

		if ( $aliases === null ) {
			return Result::newSuccess();
		}

		foreach ( $aliases as $lang => $texts ) {
			if ( in_array( 'DUPE', $texts ) ) {
				return Result::newError( [
					Error::newError(
						'found conflicting terms',
						'alias',
						'label-conflict',
						[
							'alias',
							$lang,
							'DUPE',
							'P666'
						]
					)
				] );
			}
		}

		return Result::newSuccess();
	}

	public function detectLabelDescriptionConflicts(
		$entityType,
		array $labels,
		array $descriptions = null,
		EntityId $entityId = null
	) {
		if ( $entityId && $entityId->getSerialization() === 'P666' ) {
			// simulated conflicts always conflict with P666, so if these are
			// ignored as self-conflicts, we don't need to check any labels.
			$labels = [];
		}

		foreach ( $labels as $lang => $text ) {
			if ( $descriptions !== null
				&& ( !isset( $descriptions[$lang] ) || $descriptions[$lang] !== 'DUPE' )
			) {
				continue;
			}

			if ( $text === 'DUPE' ) {
				return Result::newError( [
					Error::newError(
						'found conflicting terms',
						'label',
						'label-with-description-conflict',
						[
							'label',
							$lang,
							$text,
							'P666'
						]
					)
				] );
			}
		}

		return Result::newSuccess();
	}

	/**
	 * Returns a duplicate detector that will, consider the string "DUPE" to be a duplicate,
	 * unless a specific $returnValue is given. The same value is returned for calls to
	 * detectLabelConflicts() and detectLabelDescriptionConflicts().
	 *
	 * @param null|Result|Error[] $returnValue
	 *
	 * @return LabelDescriptionDuplicateDetector
	 */
	public function getMockLabelDescriptionDuplicateDetector( $returnValue = null ) {
		if ( is_array( $returnValue ) ) {
			if ( empty( $returnValue ) ) {
				$returnValue = Result::newSuccess();
			} else {
				$returnValue = Result::newError( $returnValue );
			}
		}

		if ( $returnValue instanceof Result ) {
			$detectLabelConflicts = $detectLabelDescriptionConflicts = function() use ( $returnValue ) {
				return $returnValue;
			};
		} else {
			$detectLabelConflicts = [ $this, 'detectLabelConflicts' ];
			$detectLabelDescriptionConflicts = [ $this, 'detectLabelDescriptionConflicts' ];
		}

		$dupeDetector = $this->getMockBuilder( LabelDescriptionDuplicateDetector::class )
			->disableOriginalConstructor()
			->getMock();

		$dupeDetector->expects( TestCase::any() )
			->method( 'detectLabelConflicts' )
			->will( TestCase::returnCallback( $detectLabelConflicts ) );

		$dupeDetector->expects( TestCase::any() )
			->method( 'detectLabelDescriptionConflicts' )
			->will( TestCase::returnCallback( $detectLabelDescriptionConflicts ) );

		return $dupeDetector;
	}

	/**
	 * @return SiteLinkConflictLookup
	 */
	public function getMockSiteLinkConflictLookup() {
		$mock = $this->getMock( SiteLinkConflictLookup::class );

		$mock->expects( TestCase::any() )
			->method( 'getConflictsForItem' )
			->will( TestCase::returnCallback( function ( Item $item ) {
				$conflicts = [];

				foreach ( $item->getSiteLinkList()->toArray() as $link ) {
					if ( $link->getPageName() === 'DUPE' ) {
						$conflicts[] = [
							'siteId' => $link->getSiteId(),
							'itemId' => new ItemId( 'Q666' ),
							'sitePage' => $link->getPageName(),
						];
					}
				}

				return $conflicts;
			} ) );

		return $mock;
	}

	/**
	 * Returns a mock fingerprint validator. If $entityType is Item::ENTITY_TYPE,
	 * the validator will detect an error for any fingerprint that contains the string "DUPE"
	 * for both the description and the label for a given language.
	 *
	 * For other entity types, the validator will consider any fingerprint valid.
	 *
	 * @see getMockLabelDescriptionDuplicateDetector()
	 *
	 * @param string $entityType
	 *
	 * @return LabelDescriptionUniquenessValidator|NullFingerprintValidator
	 */
	public function getMockFingerprintValidator( $entityType ) {
		switch ( $entityType ) {
			case Item::ENTITY_TYPE:
				return new LabelDescriptionUniquenessValidator( $this->getMockLabelDescriptionDuplicateDetector() );

			default:
				return new NullFingerprintValidator();
		}
	}

	/**
	 * Returns a TermValidatorFactory that provides mock validators.
	 * The validators consider the string "INVALID" to be invalid, and "DUPE" to be duplicates.
	 *
	 * @see getMockTermValidator()
	 * @see getMockFingerprintValidator()
	 *
	 * @return TermValidatorFactory
	 */
	public function getMockTermValidatorFactory() {
		$mock = $this->getMockBuilder( TermValidatorFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( TestCase::any() )
			->method( 'getFingerprintValidator' )
			->will( TestCase::returnCallback(
				[ $this, 'getMockFingerprintValidator' ]
			) );

		$mock->expects( TestCase::any() )
			->method( 'getLanguageValidator' )
			->will( TestCase::returnCallback(
				[ $this, 'getMockTermValidator' ]
			) );

		$mock->expects( TestCase::any() )
			->method( 'getLabelValidator' )
			->will( TestCase::returnCallback(
				[ $this, 'getMockTermValidator' ]
			) );

		$mock->expects( TestCase::any() )
			->method( 'getDescriptionValidator' )
			->will( TestCase::returnCallback(
				[ $this, 'getMockTermValidator' ]
			) );

		$mock->expects( TestCase::any() )
			->method( 'getAliasValidator' )
			->will( TestCase::returnCallback(
				[ $this, 'getMockTermValidator' ]
			) );

		return $mock;
	}

}
