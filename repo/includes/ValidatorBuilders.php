<?php

namespace Wikibase\Repo;

use DataValues\DataValue;
use DataValues\QuantityValue;
use DataValues\TimeValue;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\Validators\AlternativeValidator;
use Wikibase\Repo\Validators\CommonsMediaExistsValidator;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\DataFieldValidator;
use Wikibase\Repo\Validators\DataValueValidator;
use Wikibase\Repo\Validators\EntityExistsValidator;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\NumberRangeValidator;
use Wikibase\Repo\Validators\NumberValidator;
use Wikibase\Repo\Validators\RegexValidator;
use Wikibase\Repo\Validators\StringLengthValidator;
use Wikibase\Repo\Validators\TypeValidator;
use Wikibase\Repo\Validators\UrlSchemeValidators;
use Wikibase\Repo\Validators\UrlValidator;

/**
 * Defines validators for the basic well known data types supported by Wikibase.
 *
 * @warning: This is a low level factory for use by boostrap code only!
 * Program logic should use an instance of DataTypeValidatorFactory.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ValidatorBuilders {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var string[]
	 */
	private $urlSchemes;

	/**
	 * @var string The base URI for the vocabulary to use for units (and in the
	 * future, globes and calendars).
	 */
	private $vocabularyBaseUri;

	/**
	 * @var string The base URI wikibase concepts, for use with the validators for time and globe
	 * values. Our parsers for these data types currently have Wikidata URIs hardcoded, so we need
	 * to hardcode the URI to check them against for now.
	 *
	 * @todo: use a configurable vocabulary for calendars and reference globes, instead of
	 * hardcoding wikidata. Then replace usages of $wikidataBaseUri with $vocabularyBaseUri.
	 */
	private $wikidataBaseUri = 'http://www.wikidata.org/entity/';

	/**
	 * @var ContentLanguages
	 */
	private $contentLanguages;

	/**
	 * @var CachingCommonsMediaFileNameLookup
	 */
	private $mediaFileNameLookup;

	/**
	 * @param EntityLookup $lookup
	 * @param EntityIdParser $idParser
	 * @param string[] $urlSchemes
	 * @param string $vocabularyBaseUri The base URI for vocabulary concepts.
	 * @param ContentLanguages $contentLanguages
	 * @param CachingCommonsMediaFileNameLookup $cachingCommonsMediaFileNameLookup
	 */
	public function __construct(
		EntityLookup $lookup,
		EntityIdParser $idParser,
		array $urlSchemes,
		$vocabularyBaseUri,
		ContentLanguages $contentLanguages,
		CachingCommonsMediaFileNameLookup $cachingCommonsMediaFileNameLookup
	) {
		$this->entityLookup = $lookup;
		$this->entityIdParser = $idParser;
		$this->urlSchemes = $urlSchemes;
		$this->vocabularyBaseUri = $vocabularyBaseUri;
		$this->contentLanguages = $contentLanguages;
		$this->mediaFileNameLookup = $cachingCommonsMediaFileNameLookup;
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildItemValidators() {
		return $this->getEntityValidators( Item::ENTITY_TYPE );
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildPropertyValidators() {
		return $this->getEntityValidators( Property::ENTITY_TYPE );
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildEntityValidators() {
		return $this->getEntityValidators();
	}

	/**
	 * @param string|null $entityType
	 *
	 * @return ValueValidator[]
	 */
	private function getEntityValidators( $entityType = null ) {
		return [
			new TypeValidator( EntityIdValue::class ),
			new EntityExistsValidator( $this->entityLookup, $entityType ),
		];
	}

	/**
	 * @param int $maxLength Defaults to 400 characters. This was an arbitrary decision when it
	 * turned out that 255 was to short for descriptions.
	 *
	 * @return ValueValidator[]
	 */
	private function getCommonStringValidators( $maxLength = 400 ) {
		$validators = array();

		$validators[] = new TypeValidator( 'string' );
		//TODO: validate UTF8 (here and elsewhere)
		$validators[] = new StringLengthValidator( 1, $maxLength, 'mb_strlen' );
		$validators[] = new RegexValidator( '/^\s|[\r\n\t]|\s$/', true ); // no leading/trailing whitespace, no line breaks.

		return $validators;
	}

	/**
	 * @param string $checkExistence Either 'checkExistence' or 'doNotCheckExistence'
	 *
	 * @return ValueValidator[]
	 */
	public function buildMediaValidators( $checkExistence = 'checkExistence' ) {
		// oi_archive_name is max 255 bytes, which include a timestamp and an exclamation mark,
		// so restrict file name to 240 bytes (see UploadBase::getTitle).
		$validators = $this->getCommonStringValidators( 240 );

		// Must contain a non-empty file name with no nasty characters (see documentation of
		// $wgLegalTitleChars as well as $wgIllegalFileChars). File name extensions with digits
		// (e.g. ".jp2") are possible, as well as two characters (e.g. ".ai").
		$validators[] = new RegexValidator( '/^[^#\/:[\\\\\]{|}]+\.\w{2,}$/u' );

		if ( $checkExistence === 'checkExistence' ) {
			$validators[] = new CommonsMediaExistsValidator( $this->mediaFileNameLookup );
		}

		$topValidator = new DataValueValidator(
			new CompositeValidator( $validators ) //Note: each validator is fatal
		);

		return array( new TypeValidator( DataValue::class ), $topValidator );
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildStringValidators() {
		$validators = $this->getCommonStringValidators();

		$topValidator = new DataValueValidator(
			new CompositeValidator( $validators ) //Note: each validator is fatal
		);

		return array( new TypeValidator( DataValue::class ), $topValidator );
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildMonolingualTextValidators() {
		$validators = array();

		$validators[] = new DataFieldValidator(
			'text',
			new CompositeValidator( $this->getCommonStringValidators() ) //Note: each validator is fatal
		);

		$validators[] = new DataFieldValidator(
			'language',
			new MembershipValidator( $this->contentLanguages->getLanguages() )
		);

		$topValidator = new DataValueValidator(
			new CompositeValidator( $validators ) //Note: each validator is fatal
		);

		return array( new TypeValidator( DataValue::class ), $topValidator );
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildTimeValidators() {
		$validators = array();
		$validators[] = new TypeValidator( 'array' );

		// Expected to be a short IRI, see TimeFormatter and TimeParser.
		$urlValidator = $this->getUrlValidator( array( 'http', 'https' ), $this->wikidataBaseUri, 255 );
		//TODO: enforce well known calendar models from config

		$validators[] = new DataFieldValidator( 'calendarmodel', $urlValidator );

		// time string field
		$timeStringValidators = array();
		$timeStringValidators[] = new TypeValidator( 'string' );

		// down to the day
		$maxPrecision = TimeValue::PRECISION_DAY;
		$isoDataPattern = '/T00:00:00Z\z/';

		$timeStringValidators[] = new RegexValidator( $isoDataPattern );

		$validators[] = new DataFieldValidator(
			'time',
			new CompositeValidator( $timeStringValidators ) //Note: each validator is fatal
		);

		$precisionValidators = array();
		$precisionValidators[] = new TypeValidator( 'integer' );
		$precisionValidators[] = new NumberRangeValidator( TimeValue::PRECISION_YEAR1G, $maxPrecision );

		$validators[] = new DataFieldValidator(
			'precision',
			new CompositeValidator( $precisionValidators ) //Note: each validator is fatal
		);

		$topValidator = new DataValueValidator(
			new CompositeValidator( $validators ) //Note: each validator is fatal
		);

		return array( new TypeValidator( DataValue::class ), $topValidator );
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildCoordinateValidators() {
		$validators = array();
		$validators[] = new TypeValidator( 'array' );

		// Expected to be a short IRI, see GlobeCoordinateValue and GlobeCoordinateParser.
		$urlValidator = $this->getUrlValidator( array( 'http', 'https' ), $this->wikidataBaseUri, 255 );
		//TODO: enforce well known reference globes from config

		$validators[] = new DataFieldValidator( 'precision', new NumberValidator() );

		$validators[] = new DataFieldValidator( 'globe', $urlValidator );

		$topValidator = new DataValueValidator(
			new CompositeValidator( $validators ) //Note: each validator is fatal
		);

		return array( new TypeValidator( DataValue::class ), $topValidator );
	}

	/**
	 * @param string[] $urlSchemes List of URL schemes, e.g. 'http'
	 * @param string|null $prefix a required prefix
	 * @param int $maxLength Defaults to 500 characters. Even if URLs are unlimited in theory they
	 * should be limited to about 2000. About 500 is a reasonable compromise.
	 * @see http://stackoverflow.com/a/417184
	 *
	 * @return CompositeValidator
	 */
	private function getUrlValidator( array $urlSchemes, $prefix = null, $maxLength = 500 ) {
		$validators = array();
		$validators[] = new TypeValidator( 'string' );
		$validators[] = new StringLengthValidator( 2, $maxLength );

		$urlValidatorsBuilder = new UrlSchemeValidators();
		$urlValidators = $urlValidatorsBuilder->getValidators( $urlSchemes );
		$validators[] = new UrlValidator( $urlValidators );

		if ( $prefix !== null ) {
			// FIXME: It's currently not possible to allow both http and https at this point.
			$validators[] = $this->getPrefixValidator( $prefix, 'bad-prefix' );
		}

		return new CompositeValidator( $validators ); //Note: each validator is fatal
	}

	/**
	 * @param string $prefix
	 * @param string $errorCode
	 *
	 * @return RegexValidator
	 */
	private function getPrefixValidator( $prefix, $errorCode ) {
		$regex = '/^' . preg_quote( $prefix, '/' ) . '/';
		return new RegexValidator( $regex, false, $errorCode );
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildUrlValidators() {
		$urlValidator = $this->getUrlValidator( $this->urlSchemes );

		$topValidator = new DataValueValidator(
			$urlValidator
		);

		return array( new TypeValidator( DataValue::class ), $topValidator );
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildQuantityValidators() {
		$validators = array();
		$validators[] = new TypeValidator( 'array' );

		// the 'amount' field is already validated by QuantityValue's constructor
		// the 'digits' field is already validated by QuantityValue's constructor

		$unitValidators = new AlternativeValidator( array(
			// NOTE: "1" is always considered legal for historical reasons,
			// since we use it to represent "unitless" quantities. We could also use
			// http://qudt.org/vocab/unit#Unitless or http://www.wikidata.org/entity/Q199
			new MembershipValidator( array( '1' ) ),
			$this->getUrlValidator( array( 'http', 'https' ), $this->vocabularyBaseUri, 255 ),
		) );
		$validators[] = new DataFieldValidator( 'unit', $unitValidators );

		$topValidator = new DataValueValidator(
			new CompositeValidator( $validators ) //Note: each validator is fatal
		);

		return array( new TypeValidator( QuantityValue::class ), $topValidator );
	}

}
