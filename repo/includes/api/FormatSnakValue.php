<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use DataValues\DataValue;
use DataValues\DataValueFactory;
use DataValues\IllegalValueException;
use DataValues\StringValue;
use LogicException;
use UsageException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\OutputFormatValueFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\TypedValueFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for using value formatters.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Adam Shorland
 */
class FormatSnakValue extends ApiBase {

	/**
	 * @var OutputFormatValueFormatterFactory
	 */
	private $valueFormatterFactory;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );

		$this->setServices(
			$wikibaseRepo->getValueFormatterFactory(),
			$wikibaseRepo->getDataValueFactory(),
			$apiHelperFactory->getErrorReporter( $this )
		);
	}

	public function setServices(
		OutputFormatValueFormatterFactory $valueFormatterFactory,
		DataValueFactory $dataValueFactory,
		ApiErrorReporter $apiErrorReporter
	) {
		$this->valueFormatterFactory = $valueFormatterFactory;
		$this->dataValueFactory = $dataValueFactory;
		$this->errorReporter = $apiErrorReporter;
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.1
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$value = $this->decodeDataValue( $params['datavalue'] );
		$dataTypeId = $this->getDataTypeId( $params );

		$formatter = $this->getFormatter( $value );

		if ( $formatter instanceof TypedValueFormatter ) {
			// use data type id, if we can
			$formattedValue = $formatter->formatValue( $value, $dataTypeId );
		} else {
			// rely on value type
			$formattedValue = $formatter->format( $value );
		}

		$this->getResult()->addValue(
			null,
			'result',
			$formattedValue
		);
	}

	/**
	 * @throws LogicException
	 * @return ValueFormatter
	 */
	private function getFormatter() {
		$params = $this->extractRequestParams();

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
	 * @param string $json A JSON-encoded DataValue
	 *
	 * @throws UsageException
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

		throw new LogicException( 'ApiErrorReporter::dieUsage did not throw a UsageException' );
	}

	/**
	 * @param string $optionsParam
	 *
	 * @return FormatterOptions
	 */
	private function getOptionsObject( $optionsParam ) {
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $this->getLanguage()->getCode() );

		$options = json_decode( $optionsParam, true );

		if ( is_array( $options ) ) {
			foreach ( $options as $name => $value ) {
				$formatterOptions->setOption( $name, $value );
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
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array(
			'generate' => array(
				ApiBase::PARAM_TYPE => array(
					SnakFormatter::FORMAT_PLAIN,
					SnakFormatter::FORMAT_WIKI,
					SnakFormatter::FORMAT_HTML,
					SnakFormatter::FORMAT_HTML_WIDGET,
				),
				ApiBase::PARAM_DFLT => SnakFormatter::FORMAT_WIKI,
				ApiBase::PARAM_REQUIRED => false,
			),
			'datavalue' => array(
				ApiBase::PARAM_TYPE => 'text',
				ApiBase::PARAM_REQUIRED => true,
			),
			'datatype' => array(
				ApiBase::PARAM_TYPE => WikibaseRepo::getDefaultInstance()->getDataTypeFactory()->getTypeIds(),
				ApiBase::PARAM_REQUIRED => false,
			),
			'options' => array(
				ApiBase::PARAM_TYPE => 'text',
				ApiBase::PARAM_REQUIRED => false,
			),
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		$query = 'action=' . $this->getModuleName();
		$hello = new StringValue( 'hello' );
		$acme = new StringValue( 'http://acme.org' );

		return array(
			$query . '&' . wfArrayToCgi( array(
				'datavalue' => json_encode( $hello->toArray() ),
			) ) => 'apihelp-wbformatvalue-example-1',

			$query . '&' . wfArrayToCgi( array(
				'datavalue' => json_encode( $acme->toArray() ),
				'datatype' => 'url',
				'generate' => 'text/html',
			) ) => 'apihelp-wbformatvalue-example-2',

			//TODO: example for the options parameter, once we have something sensible to show there.
		);
	}

}
