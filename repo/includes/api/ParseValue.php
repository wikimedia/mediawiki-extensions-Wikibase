<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use ApiResult;
use DataTypes\DataTypeFactory;
use DataValues\DataValue;
use Exception;
use LogicException;
use OutOfBoundsException;
use Status;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Lib\Localizer\ExceptionLocalizer;
use Wikibase\Repo\ValueParserFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for using value parsers.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
 */
class ParseValue extends ApiBase {

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @var ValueParserFactory
	 */
	private $valueParserFactory;

	/**
	 * @var ExceptionLocalizer
	 */
	private $exceptionLocalizer;

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
			$wikibaseRepo->getDataTypeFactory(),
			$wikibaseRepo->getValueParserFactory(),
			$wikibaseRepo->getExceptionLocalizer(),
			$apiHelperFactory->getErrorReporter( $this )
		);
	}

	public function setServices(
		DataTypeFactory $dataTypeFactory,
		ValueParserFactory $valueParserFactory,
		ExceptionLocalizer $exceptionLocalizer,
		ApiErrorReporter $errorReporter
	) {
		$this->dataTypeFactory = $dataTypeFactory;
		$this->valueParserFactory = $valueParserFactory;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->errorReporter = $errorReporter;
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.1
	 */
	public function execute() {
		$parser = $this->getParser();

		$results = array();

		$params = $this->extractRequestParams();

		foreach ( $params['values'] as $value ) {
			$results[] = $this->parseStringValue( $parser, $value );
		}

		$this->outputResults( $results );
	}

	/**
	 * @return ValueParser
	 * @throws LogicException
	 */
	private function getParser() {
		$params = $this->extractRequestParams();

		$options = $this->getOptionsObject( $params['options'] );

		// Parsers are registered by datatype.
		// Note: parser used to be addressed by a name independent of datatype, using the 'parser'
		// parameter. For backwards compatibility, parsers are also registered under their old names
		// in $wgValueParsers, and thus in the ValueParserFactory.
		$name = $params['datatype'] ?: $params['parser'];

		if ( empty( $name ) ) {
			// If neither 'datatype' not 'parser' is given, tell the client to use 'datatype'.
			$this->dieUsageMsg( array( 'missingparam', 'datatype' ) );
		}

		try {
			$parser = $this->valueParserFactory->newParser( $name, $options );
		} catch ( OutOfBoundsException $ex ) {
			throw new LogicException( "No parser registered for `$name`" );
		}

		return $parser;
	}

	/**
	 * @param ValueParser $parser
	 * @param string $value
	 *
	 * @return array
	 */
	private function parseStringValue( ValueParser $parser, $value ) {
		$result = array(
			'raw' => $value
		);

		try {
			$parseResult = $parser->parse( $value );
		} catch ( ParseException $parseError ) {
			$this->addParseErrorToResult( $result, $parseError );
			return $result;
		}

		if ( $parseResult instanceof DataValue ) {
			$result['value'] = $parseResult->getArrayValue();
			$result['type'] = $parseResult->getType();
		} else {
			$result['value'] = $parseResult;
		}

		return $result;
	}

	private function addParseErrorToResult( array &$result, ParseException $parseError ) {
		$result['error'] = get_class( $parseError );

		$result['error-info'] = $parseError->getMessage();
		$result['expected-format'] = $parseError->getExpectedFormat();

		$status = $this->getExceptionStatus( $parseError );
		$this->errorReporter->addStatusToResult( $status, $result );
	}

	private function outputResults( array $results ) {
		ApiResult::setIndexedTagName( $results, 'result' );

		$this->getResult()->addValue(
			null,
			'results',
			$results
		);
	}

	/**
	 * @param string|null $optionsParam
	 *
	 * @return ParserOptions
	 */
	private function getOptionsObject( $optionsParam ) {
		$parserOptions = new ParserOptions();
		$parserOptions->setOption( ValueParser::OPT_LANG, $this->getLanguage()->getCode() );

		if ( is_string( $optionsParam ) && $optionsParam !== '' ) {
			$options = json_decode( $optionsParam, true );

			if ( !is_array( $options ) ) {
				$this->errorReporter->dieError( 'Malformed options parameter', 'malformed-options' );
			}

			foreach ( $options as $name => $value ) {
				$parserOptions->setOption( $name, $value );
			}
		}

		return $parserOptions;
	}

	/**
	 * Returns a Status object representing the given exception using a localized message.
	 *
	 * @note: The returned Status will always be fatal, that is, $status->isOk() will return false.
	 *
	 * @see getExceptionMessage().
	 *
	 * @param Exception $error
	 *
	 * @return Status
	 */
	protected function getExceptionStatus( Exception $error ) {
		$msg = $this->exceptionLocalizer->getExceptionMessage( $error );
		$status = Status::newFatal( $msg );
		$status->setResult( false, $error->getMessage() );

		return $status;
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	public function getAllowedParams() {
		return array(
			'datatype' => array(
				ApiBase::PARAM_TYPE => $this->dataTypeFactory->getTypeIds(),

				// Currently, the deprecated 'parser' parameter may be used as an
				// alternative to the 'datatype' parameter. Once 'parser' is removed,
				// 'datatype' should be required.
				ApiBase::PARAM_REQUIRED => false,
			),
			'parser' => array(
				self::PARAM_TYPE => $this->valueParserFactory->getParserIds(),

				// Use 'datatype' instead!
				// NOTE: when removing the 'parser' parameter, set 'datatype' to PARAM_REQUIRED
				self::PARAM_DEPRECATED => true,
				self::PARAM_REQUIRED => false,
			),
			'values' => array(
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
				self::PARAM_ISMULTI => true,
			),
			'options' => array(
				self::PARAM_TYPE => 'text',
				self::PARAM_REQUIRED => false,
			),
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbparsevalue&datatype=string&values=foo|bar' =>
				'apihelp-wbparsevalue-example-1',
			'action=wbparsevalue&datatype=time&values=1994-02-08&options={"precision":9}' =>
				'apihelp-wbparsevalue-example-2',
		);
	}

}
