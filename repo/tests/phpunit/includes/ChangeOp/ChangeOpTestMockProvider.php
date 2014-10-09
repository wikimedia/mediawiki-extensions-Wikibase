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
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\Lib\Store\SiteLinkCache;
use Wikibase\Validators\CompositeFingerprintValidator;
use Wikibase\Validators\CompositeValidator;
use Wikibase\Validators\DataValueValidator;
use Wikibase\Validators\LabelDescriptionUniquenessValidator;
use Wikibase\Validators\RegexValidator;
use Wikibase\Validators\SnakValidator;
use Wikibase\Validators\TermValidatorFactory;
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
	 * @var PHPUnit_Framework_TestCase
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
	private function getMockBuilder( $class ) {
		return $this->mockBuilderFactory->getMockBuilder( $class );
	}

	/**
	 * @see PHPUnit_Framework_TestCase::getMock
	 *
	 * @param string $class
	 *
	 * @return object
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
		$mock = $this->getMock( '\Wikibase\DataModel\Entity\PropertyDataTypeLookup' );
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
	 * Returns a mock validator. The term and the language "INVALID" is considered to be
	 * invalid.
	 *
	 * @return ValueValidator
	 */
	public function getMockTermValidator() {
		$mock = $this->getMockBuilder( 'ValueValidators\ValueValidator' )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'validate' )
			->will( PHPUnit_Framework_TestCase::returnCallback( function( $text ) {
				if ( $text === 'INVALID' ) {
					$error = Error::newError( 'Invalid', '', 'test-invalid' );
					return Result::newError( array( $error ) );
				} else {
					return Result::newSuccess();
				}
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

	public function detectLabelConflictsForEntity( Entity $entity ) {
		foreach ( $entity->getLabels() as $lang => $label ) {
			if ( $label === 'DUPE' ) {
				return Result::newError( array(
					Error::newError(
						'found conflicting terms',
						'label',
						'label-conflict',
						array(
							'label',
							$lang,
							$label,
							'P666'
						)
					)
				) );
			}
		}

		return Result::newSuccess();
	}

	public function detectLabelDescriptionConflictsForEntity( Entity $entity ) {
		foreach ( $entity->getLabels() as $lang => $label ) {
			$description = $entity->getDescription( $lang );

			if ( $description === null ) {
				continue;
			}

			if ( $label === 'DUPE' && $description === 'DUPE' ) {
				return Result::newError( array(
					Error::newError(
						'found conflicting terms',
						'label',
						'label-with-description-conflict',
						array(
							'label',
							$lang,
							$label,
							'Q666'
						)
					)
				) );
			}
		}

		return Result::newSuccess();
	}

	public function detectTermConflicts( $labels, $descriptions, EntityId $entityId = null ) {
		$code = ( ( $descriptions === null ) ? 'label-conflict' : 'label-with-description-conflict' );

		if ( $entityId && $entityId->getSerialization() === 'P666' ) {
			// simulated conflicts always conflict with P666, so if these are
			// ignored as self-conflicts, we don't need to check any labels.
			$labels = array();
		}

		foreach ( $labels as $lang => $label ) {

			if ( $descriptions !== null
				&& ( !isset( $descriptions[$lang] )
					|| $descriptions[$lang] !== 'DUPE' ) ) {

				continue;
			}

			if ( $label === 'DUPE' ) {
				return Result::newError( array(
					Error::newError(
						'found conflicting terms',
						'label',
						$code,
						array(
							'label',
							$lang,
							$label,
							'P666'
						)
					)
				) );
			}
		}

		return Result::newSuccess();
	}

	/**
	 * Returns a duplicate detector that will, consider the string "DUPE" to be a duplicate,
	 * unless a specific $returnValue is given.
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
			$detectLabelConflictsForEntity = function() use ( $returnValue ) {
				return $returnValue;
			};

			$detectTermConflicts = $detectLabelConflictsForEntity;
		} else {
			$detectTermConflicts = array( $this, 'detectTermConflicts' );
		}

		$dupeDetector = $this->getMockBuilder( 'Wikibase\LabelDescriptionDuplicateDetector' )
			->disableOriginalConstructor()
			->getMock();

		$dupeDetector->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'detectTermConflicts' )
			->will( PHPUnit_Framework_TestCase::returnCallback( $detectTermConflicts ) );

		return $dupeDetector;
	}

	/**
	 * @see SiteLinkLookup::getConflictsForItem
	 *
	 * The items in the return array are arrays with the following elements:
	 * - integer itemId
	 * - string siteId
	 * - string sitePage
	 *
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getSiteLinkConflictsForItem( Item $item ) {
		$conflicts = array();

		foreach ( $item->getSiteLinks() as $link ) {
			$page = $link->getPageName();
			$site = $link->getSiteId();

			if ( $page === 'DUPE' ) {
				//NOTE: some tests may rely on these exact values!
				$conflicts[] = array(
					'itemId' => 666,
					'siteId' => $site,
					'sitePage' => $page
				);
			}
		}

		return $conflicts;
	}

	/**
	 * @param array $returnValue
	 *
	 * @return SiteLinkCache
	 */
	public function getMockSitelinkCache( $returnValue = null ) {
		if ( is_array( $returnValue ) ) {
			$getConflictsForItem = function() use ( $returnValue ) {
				return $returnValue;
			};
		} else {
			$getConflictsForItem = array( $this, 'getSiteLinkConflictsForItem' );
		}

		$mock = $this->getMock( 'Wikibase\Lib\Store\SiteLinkCache' );
		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'getConflictsForItem' )
			->will( PHPUnit_Framework_TestCase::returnCallback( $getConflictsForItem ) );
		return $mock;
	}

	/**
	 * @return ClaimGuidGenerator
	 */
	public function getMockGuidGenerator() {
		return new ClaimGuidGenerator();
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
	 * @param $entityType
	 *
	 * @return LabelDescriptionUniquenessValidator|CompositeFingerprintValidator
	 */
	public function getMockFingerprintValidator( $entityType ) {
		switch ( $entityType ) {
			case Item::ENTITY_TYPE:
				return new LabelDescriptionUniquenessValidator( $this->getMockLabelDescriptionDuplicateDetector() );

			default:
				return new CompositeFingerprintValidator( array() );
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
		$mock = $this->getMockBuilder( 'Wikibase\Validators\TermValidatorFactory' )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'getFingerprintValidator' )
			->will( PHPUnit_Framework_TestCase::returnCallback(
				array( $this, 'getMockFingerprintValidator' )
			) );

		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'getLanguageValidator' )
			->will( PHPUnit_Framework_TestCase::returnCallback(
				array( $this, 'getMockTermValidator' )
			) );

		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'getLabelValidator' )
			->will( PHPUnit_Framework_TestCase::returnCallback(
				array( $this, 'getMockTermValidator' )
			) );

		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'getDescriptionValidator' )
			->will( PHPUnit_Framework_TestCase::returnCallback(
				array( $this, 'getMockTermValidator' )
			) );

		$mock->expects( PHPUnit_Framework_TestCase::any() )
			->method( 'getAliasValidator' )
			->will( PHPUnit_Framework_TestCase::returnCallback(
				array( $this, 'getMockTermValidator' )
			) );

		return $mock;
	}
}
