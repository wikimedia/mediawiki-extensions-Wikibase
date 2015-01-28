<?php

namespace Wikibase\Api;

use ApiBase;
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
 */
class FormatSnakValue extends ApiWikibase {

	/**
	 * @var null|OutputFormatValueFormatterFactory
	 */
	protected $formatterFactory = null;

	/**
	 * @var null|DataValueFactory
	 */
	protected $valueFactory = null;

	/**
	 * @return OutputFormatValueFormatterFactory
	 */
	protected function getFormatterFactory() {
		if ( $this->formatterFactory === null ) {
			$this->formatterFactory = WikibaseRepo::getDefaultInstance()->getValueFormatterFactory();
		}

		return $this->formatterFactory;
	}

	/**
	 * @return DataValueFactory
	 */
	protected function getValueFactory() {
		if ( $this->valueFactory === null ) {
			$this->valueFactory = WikibaseRepo::getDefaultInstance()->getDataValueFactory();
		}

		return $this->valueFactory;
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
		$formatter = $this->getFormatterFactory()->getValueFormatter( $params['generate'], $options );

		// Paranoid check, should never fail since we only accept well known values for the 'generate' parameter
		if ( $formatter === null ) {
			throw new LogicException( 'Could not obtain a ValueFormatter instance for ' . $params['generate'] );
		}

		return $formatter;
	}

	/**
	 * @since 0.1
	 *
	 * @param string $json A JSON-encoded DataValue
	 *
	 * @throws UsageException
	 * @throws LogicException
	 * @return DataValue
	 */
	protected function decodeDataValue( $json ) {
		$data = \FormatJson::decode( $json, true );

		if ( !is_array( $data ) ) {
			$this->dieError( 'Failed to decode datavalue', 'baddatavalue' );
		}

		try {
			$value = $this->getValueFactory()->newFromArray( $data );
			return $value;
		} catch ( IllegalValueException $ex ) {
			$this->dieException( $ex, 'baddatavalue' );
		}

		throw new LogicException( 'ApiBase::dieUsage did not throw a UsageException' );
	}

	/**
	 * @since 0.1
	 *
	 * @param string $optionsParam
	 *
	 * @return FormatterOptions
	 */
	protected function getOptionsObject( $optionsParam ) {
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $this->getLanguage()->getCode() );

		$options = \FormatJson::decode( $optionsParam, true );

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
	 * @return string|null
	 */
	protected function getDataTypeId( array $params ) {
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
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'datatype' => array(
				ApiBase::PARAM_TYPE => WikibaseRepo::getDefaultInstance()->getDataTypeFactory()->getTypeIds(),
				ApiBase::PARAM_REQUIRED => false,
			),
			'options' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
			),
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 *
	 * @return array
	 */
	protected function getExamplesMessages() {
		$query = "action=" . $this->getModuleName() ;
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
