<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
use DataValues\DataValue;
use DataValues\IllegalValueException;
use DataValues\StringValue;
use InvalidArgumentException;
use LogicException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\DataValueFactory;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Formatters\TypedValueFormatter;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesException;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for using value formatters.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Addshore
 * @author Marius Hoch
 */
class FormatSnakValue extends ApiBase {

	/**
	 * @var OutputFormatValueFormatterFactory
	 */
	private $valueFormatterFactory;

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @see ApiBase::__construct
	 *
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param OutputFormatValueFormatterFactory $valueFormatterFactory
	 * @param OutputFormatSnakFormatterFactory $snakFormatterFactory
	 * @param DataValueFactory $dataValueFactory
	 * @param ApiErrorReporter $apiErrorReporter
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		OutputFormatValueFormatterFactory $valueFormatterFactory,
		OutputFormatSnakFormatterFactory $snakFormatterFactory,
		DataValueFactory $dataValueFactory,
		ApiErrorReporter $apiErrorReporter
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->valueFormatterFactory = $valueFormatterFactory;
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->dataValueFactory = $dataValueFactory;
		$this->errorReporter = $apiErrorReporter;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->getMain()->setCacheMode( 'public' );

		$params = $this->extractRequestParams();
		$this->requireMaxOneParameter( $params, 'property', 'datatype' );

		try {
			$value = $this->decodeDataValue( $params['datavalue'] );
			$dataTypeId = $this->getDataTypeId( $params );
			$formattedValue = $this->formatValue( $params, $value, $dataTypeId );
		} catch ( FederatedPropertiesException $ex ) {
			$this->errorReporter->dieException(
				new FederatedPropertiesException( wfMessage( 'wikibase-federated-properties-failed-request-api-error-message' )->text() ),
				'federated-properties-failed-request',
				503,
				[ 'property' => $params['property'] ]
			);
		} catch ( InvalidArgumentException $invalidArgumentException ) {
			$this->errorReporter->dieException(
				$invalidArgumentException,
				'param-illegal'
			);
		}

		$this->getResult()->addValue(
			null,
			'result',
			$formattedValue
		);
	}

	private function formatValue( $params, $value, $dataTypeId ) {
		$snak = null;
		if ( isset( $params['property'] ) ) {
			$snak = $this->decodeSnak( $params['property'], $value );
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
	private function getValueFormatter( $params ) {
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
	private function getSnakFormatter( $params ) {
		$options = $this->getOptionsObject( $params['options'] );
		$formatter = $this->snakFormatterFactory->getSnakFormatter( $params['generate'], $options );

		return $formatter;
	}

	/**
	 * @param string $propertyIdSerialization
	 * @param DataValue $dataValue
	 *
	 * @return PropertyValueSnak
	 */
	private function decodeSnak( $propertyIdSerialization, DataValue $dataValue ) {
		try {
			$propertyId = new PropertyId( $propertyIdSerialization );
		} catch ( InvalidArgumentException $ex ) {
			$this->errorReporter->dieException( $ex, 'badpropertyid' );
		}

		return new PropertyValueSnak( $propertyId, $dataValue );
	}

	/**
	 * @param string $json A JSON-encoded DataValue
	 *
	 * @throws ApiUsageException
	 * @throws LogicException
	 * @return DataValue
	 */
	private function decodeDataValue( $json ) {
		$data = json_decode( $json, true );

		if ( !is_array( $data ) ) {
			$this->errorReporter->dieError( 'Failed to decode datavalue', 'baddatavalue' );
		}

		try {
			$value = $this->dataValueFactory->newFromArray( $data );
			return $value;
		} catch ( IllegalValueException $ex ) {
			$this->errorReporter->dieException( $ex, 'baddatavalue' );
		}

		throw new LogicException( 'ApiErrorReporter::dieException did not throw an ApiUsageException' );
	}

	/**
	 * @param string|null $optionsParam
	 *
	 * @return FormatterOptions
	 */
	private function getOptionsObject( $optionsParam ) {
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $this->getLanguage()->getCode() );

		if ( is_string( $optionsParam ) && $optionsParam !== '' ) {
			$options = json_decode( $optionsParam, true );

			if ( is_array( $options ) ) {
				foreach ( $options as $name => $value ) {
					$formatterOptions->setOption( $name, $value );
				}
			}
		}

		return $formatterOptions;
	}

	/**
	 * Returns the data type ID specified by the parameters.
	 *
	 * @param array $params
	 *
	 * @return string|null
	 */
	private function getDataTypeId( array $params ) {
		//TODO: could be looked up based on a property ID
		return $params['datatype'];
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams() {
		return [
			'generate' => [
				self::PARAM_TYPE => [
					SnakFormatter::FORMAT_PLAIN,
					SnakFormatter::FORMAT_WIKI,
					SnakFormatter::FORMAT_HTML,
					SnakFormatter::FORMAT_HTML_VERBOSE,
					SnakFormatter::FORMAT_HTML_VERBOSE_PREVIEW,
				],
				self::PARAM_DFLT => SnakFormatter::FORMAT_WIKI,
				self::PARAM_REQUIRED => false,
			],
			'datavalue' => [
				self::PARAM_TYPE => 'text',
				self::PARAM_REQUIRED => true,
			],
			'datatype' => [
				self::PARAM_TYPE => WikibaseRepo::getDefaultInstance()->getDataTypeFactory()->getTypeIds(),
				self::PARAM_REQUIRED => false,
			],
			'property' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => false,
			],
			'options' => [
				self::PARAM_TYPE => 'text',
				self::PARAM_REQUIRED => false,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		$query = 'action=' . $this->getModuleName();
		$hello = new StringValue( 'hello' );
		$acme = new StringValue( 'http://acme.org' );

		return [
			$query . '&' . wfArrayToCgi( [
				'datavalue' => json_encode( $hello->toArray() ),
			] ) => 'apihelp-wbformatvalue-example-1',

			$query . '&' . wfArrayToCgi( [
				'datavalue' => json_encode( $acme->toArray() ),
				'datatype' => 'url',
				'generate' => 'text/html',
			] ) => 'apihelp-wbformatvalue-example-2',

			//TODO: example for the options parameter, once we have something sensible to show there.
		];
	}

}
