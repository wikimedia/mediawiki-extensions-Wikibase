<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use ApiResult;
use Wikibase\Lib\DataTypeFactory;
use DataValues\DataValue;
use Exception;
use LogicException;
use OutOfBoundsException;
use Status;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use ValueValidators\Error;
use ValueValidators\ValueValidator;
use Wikibase\Repo\DataTypeValidatorFactory;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Repo\ValueParserFactory;

/**
 * API module for using value parsers.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Addshore
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
	 * @var DataTypeValidatorFactory
	 */
	private $dataTypeValidatorFactory;

	/**
	 * @var ValidatorErrorLocalizer
	 */
	private $validatorErrorLocalizer;

	/**
	 * @var ExceptionLocalizer
	 */
	private $exceptionLocalizer;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @see ApiBase::__construct
	 *
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param DataTypeFactory $dataTypeFactory
	 * @param ValueParserFactory $valueParserFactory
	 * @param DataTypeValidatorFactory $dataTypeValidatorFactory
	 * @param ExceptionLocalizer $exceptionLocalizer
	 * @param ValidatorErrorLocalizer $validatorErrorLocalizer
	 * @param ApiErrorReporter $errorReporter
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		DataTypeFactory $dataTypeFactory,
		ValueParserFactory $valueParserFactory,
		DataTypeValidatorFactory $dataTypeValidatorFactory,
		ExceptionLocalizer $exceptionLocalizer,
		ValidatorErrorLocalizer $validatorErrorLocalizer,
		ApiErrorReporter $errorReporter
	) {
		parent::__construct( $mainModule, $moduleName );
		$this->dataTypeFactory = $dataTypeFactory;
		$this->valueParserFactory = $valueParserFactory;
		$this->dataTypeValidatorFactory = $dataTypeValidatorFactory;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->validatorErrorLocalizer = $validatorErrorLocalizer;
		$this->errorReporter = $errorReporter;
	}

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {
		$this->getMain()->setCacheMode( 'public' );

		$parser = $this->getParser();

		$results = [];

		$params = $this->extractRequestParams();
		$validator = $params['validate'] ? $this->getValidator() : null;

		foreach ( $params['values'] as $value ) {
			$results[] = $this->parseStringValue( $parser, $value, $validator );
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
			$this->errorReporter->dieWithError(
				'wikibase-api-not-recognized-datatype',
				'param-illegal'
			);
		}

		try {
			$parser = $this->valueParserFactory->newParser( $name, $options );
			return $parser;
		} catch ( OutOfBoundsException $ex ) {
			$this->errorReporter->dieWithError(
				'wikibase-api-not-recognized-datatype',
				'unknown-datatype'
			);
			throw new LogicException( 'dieError() did not throw an exception' );
		}
	}

	/**
	 * @return ValueValidator
	 */
	private function getValidator() {
		$params = $this->extractRequestParams();

		$name = $params['datatype'];

		if ( empty( $name ) ) {
			// 'datatype' parameter is required for validation.
			$this->errorReporter->dieWithError(
				'wikibase-api-not-recognized-datatype',
				'param-illegal'
			);
		}

		// Note: For unknown datatype, we'll get an empty list.
		$validators = $this->dataTypeValidatorFactory->getValidators( $name );
		return $this->wrapValidators( $validators );
	}

	/**
	 * @param ValueValidator[] $validators
	 *
	 * @return ValueValidator
	 */
	private function wrapValidators( array $validators ) {
		if ( count( $validators ) === 1 ) {
			return reset( $validators );
		}

		return new CompositeValidator( $validators, true );
	}

	/**
	 * @param ValueParser $parser
	 * @param string $value
	 * @param ValueValidator|null $validator
	 *
	 * @return array
	 */
	private function parseStringValue( ValueParser $parser, $value, ValueValidator $validator = null ) {
		$result = [
			'raw' => $value
		];

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

		if ( $validator ) {
			$validatorResult = $validator->validate( $parseResult );
			$validationStatus = $this->validatorErrorLocalizer->getResultStatus( $validatorResult );

			$result['valid'] = $validationStatus->isOK();

			if ( !$validationStatus->isOK() ) {
				$result['error'] = 'ValidationError';
				$this->errorReporter->addStatusToResult( $validationStatus, $result );
				$result['validation-errors'] = $this->getValidatorErrorCodes( $validatorResult->getErrors() );
			}
		}

		return $result;
	}

	/**
	 * @param Error[] $errors
	 *
	 * @return string[]
	 */
	private function getValidatorErrorCodes( array $errors ) {
		return array_map(
			function ( Error $error ) {
				return $error->getCode();
			},
			$errors
		);
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
	 * @note: The returned Status will always be fatal, that is, $status->isOK() will return false.
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
		return [
			'datatype' => [
				ApiBase::PARAM_TYPE => $this->dataTypeFactory->getTypeIds(),

				// Currently, the deprecated 'parser' parameter may be used as an
				// alternative to the 'datatype' parameter. Once 'parser' is removed,
				// 'datatype' should be required.
				ApiBase::PARAM_REQUIRED => false,
			],
			'parser' => [
				self::PARAM_TYPE => $this->valueParserFactory->getParserIds(),

				// Use 'datatype' instead!
				// NOTE: when removing the 'parser' parameter, set 'datatype' to PARAM_REQUIRED
				self::PARAM_DEPRECATED => true,
				self::PARAM_REQUIRED => false,
			],
			'values' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
				self::PARAM_ISMULTI => true,
			],
			'options' => [
				self::PARAM_TYPE => 'text',
				self::PARAM_REQUIRED => false,
			],
			'validate' => [
				ApiBase::PARAM_TYPE => 'boolean',
			],
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return [
			'action=wbparsevalue&datatype=string&values=foo|bar' =>
				'apihelp-wbparsevalue-example-1',
			'action=wbparsevalue&datatype=time&values=1994-02-08&options={"precision":9}' =>
				'apihelp-wbparsevalue-example-2',
			'action=wbparsevalue&datatype=time&validate&values=1994-02-08&options={"precision":14}' =>
				'apihelp-wbparsevalue-example-3',
		];
	}

}
