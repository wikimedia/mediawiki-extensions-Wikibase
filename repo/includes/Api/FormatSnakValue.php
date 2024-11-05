<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use DataValues\DataValue;
use DataValues\IllegalValueException;
use DataValues\StringValue;
use DataValues\TimeValue;
use Deserializers\Exceptions\DeserializationException;
use InvalidArgumentException;
use LogicException;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MediaWiki\Languages\LanguageNameUtils;
use ValueFormatters\FormatterOptions;
use ValueFormatters\FormattingException;
use ValueFormatters\QuantityFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Deserializers\SnakValueDeserializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\DataValueFactory;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\Formatters\ShowCalendarModelDecider;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Formatters\TypedValueFormatter;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesException;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Stats\IBufferingStatsdDataFactory;
use Wikimedia\Stats\NullStatsdDataFactory;

/**
 * API module for using value formatters.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Addshore
 * @author Marius Hoch
 */
class FormatSnakValue extends ApiBase {

	private OutputFormatValueFormatterFactory $valueFormatterFactory;
	private OutputFormatSnakFormatterFactory $snakFormatterFactory;
	private DataTypeFactory $dataTypeFactory;
	private DataValueFactory $dataValueFactory;
	private ApiErrorReporter $errorReporter;
	private LanguageNameUtils $languageNameUtils;
	private IBufferingStatsdDataFactory $stats;
	private EntityIdParser $entityIdParser;
	private PropertyDataTypeLookup $dataTypeLookup;
	private SnakValueDeserializer $snakValueDeserializer;

	/**
	 * @see ApiBase::__construct
	 */
	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		OutputFormatValueFormatterFactory $valueFormatterFactory,
		OutputFormatSnakFormatterFactory $snakFormatterFactory,
		DataTypeFactory $dataTypeFactory,
		DataValueFactory $dataValueFactory,
		ApiErrorReporter $apiErrorReporter,
		LanguageNameUtils $languageNameUtils,
		?IBufferingStatsdDataFactory $stats,
		EntityIdParser $entityIdParser,
		PropertyDataTypeLookup $dataTypeLookup,
		SnakValueDeserializer $snakValueDeserializer
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->valueFormatterFactory = $valueFormatterFactory;
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->dataValueFactory = $dataValueFactory;
		$this->errorReporter = $apiErrorReporter;
		$this->languageNameUtils = $languageNameUtils;
		$this->stats = $stats ?: new NullStatsdDataFactory();
		$this->entityIdParser = $entityIdParser;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->snakValueDeserializer = $snakValueDeserializer;
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		LanguageNameUtils $languageNameUtils,
		IBufferingStatsdDataFactory $stats,
		ApiHelperFactory $apiHelperFactory,
		DataTypeFactory $dataTypeFactory,
		DataValueFactory $dataValueFactory,
		EntityIdParser $entityIdParser,
		PropertyDataTypeLookup $dataTypeLookup,
		OutputFormatSnakFormatterFactory $snakFormatterFactory,
		SnakValueDeserializer $snakValueDeserializer,
		OutputFormatValueFormatterFactory $valueFormatterFactory
	): self {
		return new self(
			$mainModule,
			$moduleName,
			$valueFormatterFactory,
			$snakFormatterFactory,
			$dataTypeFactory,
			$dataValueFactory,
			$apiHelperFactory->getErrorReporter( $mainModule ),
			$languageNameUtils,
			$stats,
			$entityIdParser,
			$dataTypeLookup,
			$snakValueDeserializer
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
		$this->getMain()->setCacheMode( 'public' );

		$params = $this->extractRequestParams();
		$this->requireMaxOneParameter( $params, 'property', 'datatype' );

		try {
			$value = $this->decodeDataValue( $params );
			$dataTypeId = $this->getDataTypeId( $params );
			$formattedValue = $this->formatValue( $params, $value, $dataTypeId );
		} catch ( FederatedPropertiesException $ex ) {
			$this->errorReporter->dieException(
				new FederatedPropertiesException(
					$this->msg( 'wikibase-federated-properties-failed-request-api-error-message' )->text()
				),
				'federated-properties-failed-request',
				503,
				[ 'property' => $params['property'] ]
			);
		} catch ( InvalidArgumentException | FormattingException $exception ) {
			$this->errorReporter->dieException(
				$exception,
				'param-illegal'
			);
		}

		$this->getResult()->addValue(
			null,
			'result',
			$formattedValue
		);
	}

	private function formatValue( array $params, DataValue $value, ?string $dataTypeId ): string {
		$snak = null;
		if ( isset( $params['property'] ) ) {
			$snak = new PropertyValueSnak( $this->parsePropertyId( $params['property'] ), $value );
		}

		if ( $snak ) {
			$snakFormatter = $this->getSnakFormatter( $params );
			$formattedValue = $snakFormatter->formatSnak( $snak );
		} else {
			$valueFormatter = $this->getValueFormatter( $params );

			if ( $valueFormatter instanceof TypedValueFormatter ) {
				// use data type id, if we can
				$formattedValue = $valueFormatter->formatValue( $value, $dataTypeId );
			} else {
				// rely on value type
				$formattedValue = $valueFormatter->format( $value );
			}
		}

		return $formattedValue;
	}

	/**
	 * @throws LogicException
	 * @return ValueFormatter
	 */
	private function getValueFormatter( array $params ): ValueFormatter {
		$options = $this->getOptionsObject( $params['options'] );
		$formatter = $this->valueFormatterFactory->getValueFormatter( $params['generate'], $options );

		// Paranoid check:
		// should never fail since we only accept well known values for the 'generate' parameter
		if ( $formatter === null ) {
			throw new LogicException(
				'Could not obtain a ValueFormatter instance for ' . $params['generate']
			);
		}

		return $formatter;
	}

	/**
	 * @throws LogicException
	 * @return SnakFormatter
	 */
	private function getSnakFormatter( array $params ): SnakFormatter {
		$options = $this->getOptionsObject( $params['options'] );
		$formatter = $this->snakFormatterFactory->getSnakFormatter( $params['generate'], $options );

		return $formatter;
	}

	/**
	 * @throws ApiUsageException
	 * @throws LogicException
	 */
	private function decodeDataValue( array $params ): DataValue {
		$data = json_decode( $params['datavalue'], true );

		if ( !is_array( $data ) ) {
			$this->errorReporter->dieError( 'Failed to decode datavalue', 'baddatavalue' );
		}
		'@phan-var array $data';

		$dataType = $this->getDataTypeId( $params );

		try {
			return $dataType
				? $this->snakValueDeserializer->deserialize( $dataType, $data )
				: $this->dataValueFactory->newFromArray( $data );
		} catch ( IllegalValueException | DeserializationException $ex ) {
			$this->errorReporter->dieException( $ex, 'baddatavalue' );
		}

		throw new LogicException( 'ApiErrorReporter::dieException did not throw an ApiUsageException' );
	}

	private function getOptionsObject( ?string $optionsParam ): FormatterOptions {
		$formatterOptions = new FormatterOptions();
		$this->setValidOption( $formatterOptions, ValueFormatter::OPT_LANG, $this->getLanguage()->getCode() );

		if ( is_string( $optionsParam ) && $optionsParam !== '' ) {
			$options = json_decode( $optionsParam, true );

			if ( is_array( $options ) ) {
				foreach ( $options as $name => $value ) {
					$this->stats->increment( "wikibase.repo.api.formatvalue.options.$name" );
					$this->setValidOption( $formatterOptions, $name, $value );
				}
			}
		}

		return $formatterOptions;
	}

	private function setValidOption( FormatterOptions $options, string $option, $value ): void {
		switch ( $option ) {
			case ValueFormatter::OPT_LANG:
				if ( !is_string( $value ) ) {
					$this->errorReporter->dieWithError(
						'wikibase-api-invalid-formatter-options-lang',
						'param-illegal'
					);
				}
				if ( !$this->languageNameUtils->isValidBuiltInCode( $value ) ) {
					// silently ignore invalid strings, like most of Wikibase / MediaWiki
					$value = 'und';
				}
				break;
			case QuantityFormatter::OPT_APPLY_ROUNDING:
				if ( !is_bool( $value ) && !is_int( $value ) ) {
					$this->errorReporter->dieWithError(
						'wikibase-api-invalid-formatter-options-apply-rounding',
						'param-illegal'
					);
				}
				break;
			case QuantityFormatter::OPT_APPLY_UNIT:
				if ( !is_bool( $value ) ) {
					$this->errorReporter->dieWithError(
						'wikibase-api-invalid-formatter-options-apply-unit',
						'param-illegal'
					);
				}
				break;
			case ShowCalendarModelDecider::OPT_SHOW_CALENDAR:
				if ( !is_bool( $value ) && $value !== 'auto' ) {
					$this->errorReporter->dieWithError(
						'wikibase-api-invalid-formatter-options-showcalendar',
						'param-illegal'
					);
				}
				break;
		}
		$options->setOption( $option, $value );
	}

	/**
	 * Returns the data type ID specified by the parameters.
	 *
	 * @param array $params
	 *
	 * @return string|null
	 */
	private function getDataTypeId( array $params ): ?string {
		if ( isset( $params['datatype'] ) ) {
			return $params['datatype'];
		}
		if ( isset( $params['property'] ) ) {
			return $this->lookUpPropertyDataType( $params['property'] );
		}

		return null;
	}

	private function parsePropertyId( string $propertyIdSerialization ): PropertyId {
		try {
			/** @var PropertyId $propertyId */
			$propertyId = $this->entityIdParser->parse( $propertyIdSerialization );
			'@phan-var PropertyId $propertyId';
			return $propertyId;
		} catch ( InvalidArgumentException $ex ) {
			$this->errorReporter->dieException( $ex, 'badpropertyid' );
		}
	}

	private function lookUpPropertyDataType( string $id ): ?string {
		try {
			return $this->dataTypeLookup->getDataTypeIdForProperty( $this->parsePropertyId( $id ) );
		} catch ( PropertyDataTypeLookupException $e ) {
			return null; // property not found will be handled by the snak formatter later
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return [
			'generate' => [
				ParamValidator::PARAM_TYPE => [
					SnakFormatter::FORMAT_PLAIN,
					SnakFormatter::FORMAT_WIKI,
					SnakFormatter::FORMAT_HTML,
					SnakFormatter::FORMAT_HTML_VERBOSE,
					SnakFormatter::FORMAT_HTML_VERBOSE_PREVIEW,
				],
				ParamValidator::PARAM_DEFAULT => SnakFormatter::FORMAT_WIKI,
				ParamValidator::PARAM_REQUIRED => false,
			],
			'datavalue' => [
				ParamValidator::PARAM_TYPE => 'text',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'datatype' => [
				ParamValidator::PARAM_TYPE => $this->dataTypeFactory->getTypeIds(),
				ParamValidator::PARAM_REQUIRED => false,
			],
			'property' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'options' => [
				ParamValidator::PARAM_TYPE => 'text',
				ParamValidator::PARAM_REQUIRED => false,
				ApiBase::PARAM_HELP_MSG => [
					'apihelp-wbformatvalue-param-options',
					ValueFormatter::OPT_LANG,
					QuantityFormatter::OPT_APPLY_ROUNDING,
					QuantityFormatter::OPT_APPLY_UNIT,
					ShowCalendarModelDecider::OPT_SHOW_CALENDAR,
				],
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		$query = 'action=' . $this->getModuleName();
		$hello = new StringValue( 'hello' );
		$acme = new StringValue( 'http://acme.org' );
		$einsteinDob = new TimeValue(
			'+1879-03-14T00:00:00Z',
			0, 0, 0,
			TimeValue::PRECISION_DAY,
			TimeValue::CALENDAR_GREGORIAN
		);

		return [
			$query . '&' . wfArrayToCgi( [
				'datavalue' => json_encode( $hello->toArray() ),
			] ) => 'apihelp-wbformatvalue-example-1',

			$query . '&' . wfArrayToCgi( [
				'datavalue' => json_encode( $acme->toArray() ),
				'datatype' => 'url',
				'generate' => 'text/html',
			] ) => 'apihelp-wbformatvalue-example-2',

			$query . '&' . wfArrayToCgi( [
				'datavalue' => json_encode( $einsteinDob->toArray() ),
				'datatype' => 'time',
				'generate' => 'text/plain',
				'options' => json_encode( [
					ShowCalendarModelDecider::OPT_SHOW_CALENDAR => 'auto',
				] ),
			] ) => 'apihelp-wbformatvalue-example-3',
		];
	}

}
