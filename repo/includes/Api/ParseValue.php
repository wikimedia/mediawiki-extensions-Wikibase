<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use ApiResult;
use DataValues\DataValue;
use Exception;
use IBufferingStatsdDataFactory;
use InvalidArgumentException;
use LogicException;
use NullStatsdDataFactory;
use OutOfBoundsException;
use Status;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use ValueValidators\Error;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Repo\DataTypeValidatorFactory;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Repo\ValueParserFactory;
use Wikimedia\ParamValidator\ParamValidator;

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
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/** @var IBufferingStatsdDataFactory */
	private $stats;

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
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param ApiErrorReporter $errorReporter
	 * @param IBufferingStatsdDataFactory|null $stats
	 */
	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		DataTypeFactory $dataTypeFactory,
		ValueParserFactory $valueParserFactory,
		DataTypeValidatorFactory $dataTypeValidatorFactory,
		ExceptionLocalizer $exceptionLocalizer,
		ValidatorErrorLocalizer $validatorErrorLocalizer,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		ApiErrorReporter $errorReporter,
		IBufferingStatsdDataFactory $stats = null
	) {
		parent::__construct( $mainModule, $moduleName );
		$this->dataTypeFactory = $dataTypeFactory;
		$this->valueParserFactory = $valueParserFactory;
		$this->dataTypeValidatorFactory = $dataTypeValidatorFactory;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->validatorErrorLocalizer = $validatorErrorLocalizer;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->errorReporter = $errorReporter;
		$this->stats = $stats ?: new NullStatsdDataFactory();
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		IBufferingStatsdDataFactory $stats,
		ApiHelperFactory $apiHelperFactory,
		DataTypeFactory $dataTypeFactory,
		DataTypeValidatorFactory $dataTypeValidatorFactory,
		ExceptionLocalizer $exceptionLocalizer,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		ValidatorErrorLocalizer $validatorErrorLocalizer,
		ValueParserFactory $valueParserFactory
	): self {
		return new self(
			$mainModule,
			$moduleName,
			$dataTypeFactory,
			$valueParserFactory,
			$dataTypeValidatorFactory,
			$exceptionLocalizer,
			$validatorErrorLocalizer,
			$propertyDataTypeLookup,
			$apiHelperFactory->getErrorReporter( $mainModule ),
			$stats
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
		$this->getMain()->setCacheMode( 'public' );

		$parser = $this->getParser();

		$results = [];

		$params = $this->extractRequestParams();
		$this->requireMaxOneParameter( $params, 'property', 'datatype', 'parser' );
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
	private function getParser(): ValueParser {
		$params = $this->extractRequestParams();

		$options = $this->getOptionsObject( $params['options'] );

		// Parsers are registered by datatype.
		// Note: parser used to be addressed by a name independent of datatype, using the 'parser'
		// parameter. For backwards compatibility, parsers are also registered under their old names
		// in the ValueParserFactory (see WikibaseRepo.ServiceWiring.php).
		$name = $params['datatype'] ?: $params['parser'];

		if ( empty( $name ) && isset( $params['property'] ) ) {
			try {
				$propertyId = new NumericPropertyId( $params['property'] );
			} catch ( InvalidArgumentException $ex ) {
				$this->errorReporter->dieWithError(
					'wikibase-api-invalid-property-id',
					'param-illegal'
				);
			}
			try {
				$name = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );
			} catch ( PropertyDataTypeLookupException $ex ) {
				$this->errorReporter->dieWithError(
					'wikibase-api-invalid-property-id', // TODO separate error for valid-but-missing property ID?
					'param-illegal'
				);
			}
		}

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

	private function getValidator(): ValueValidator {
		$params = $this->extractRequestParams();

		$name = $params['datatype'];

		if ( empty( $name ) && isset( $params['property'] ) ) {
			$propertyId = new NumericPropertyId( $params['property'] );
			$name = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );
		}

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
	private function wrapValidators( array $validators ): ValueValidator {
		if ( count( $validators ) === 1 ) {
			return reset( $validators );
		}

		return new CompositeValidator( $validators, true );
	}

	private function parseStringValue( ValueParser $parser, string $value, ?ValueValidator $validator ): array {
		$result = [
			'raw' => $value,
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
	private function getValidatorErrorCodes( array $errors ): array {
		return array_map(
			function ( Error $error ) {
				return $error->getCode();
			},
			$errors
		);
	}

	private function addParseErrorToResult( array &$result, ParseException $parseError ): void {
		$result['error'] = get_class( $parseError );

		$result['error-info'] = $parseError->getMessage();
		$result['expected-format'] = $parseError->getExpectedFormat();

		$status = $this->getExceptionStatus( $parseError );
		$this->errorReporter->addStatusToResult( $status, $result );
	}

	private function outputResults( array $results ): void {
		ApiResult::setIndexedTagName( $results, 'result' );

		$this->getResult()->addValue(
			null,
			'results',
			$results
		);
	}

	private function getOptionsObject( ?string $optionsParam ): ParserOptions {
		$parserOptions = new ParserOptions();
		$parserOptions->setOption( ValueParser::OPT_LANG, $this->getLanguage()->getCode() );

		if ( is_string( $optionsParam ) && $optionsParam !== '' ) {
			$options = json_decode( $optionsParam, true );

			if ( !is_array( $options ) ) {
				$this->errorReporter->dieError( 'Malformed options parameter', 'malformed-options' );
			}

			foreach ( $options as $name => $value ) {
				$this->stats->increment( "wikibase.repo.api.parsevalue.options.$name" );
				$parserOptions->setOption( $name, $value );
			}
		}

		return $parserOptions;
	}

	/**
	 * Returns a Status object representing the given exception using a localized message.
	 *
	 * @note The returned Status will always be fatal, that is, $status->isOK() will return false.
	 *
	 * @see getExceptionMessage().
	 *
	 * @param Exception $error
	 *
	 * @return Status
	 */
	protected function getExceptionStatus( Exception $error ): Status {
		$msg = $this->exceptionLocalizer->getExceptionMessage( $error );
		$status = Status::newFatal( $msg );
		$status->setResult( false, $error->getMessage() );

		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams(): array {
		return [
			'datatype' => [
				ParamValidator::PARAM_TYPE => $this->dataTypeFactory->getTypeIds(),
				ParamValidator::PARAM_REQUIRED => false,
			],
			'property' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'parser' => [
				ParamValidator::PARAM_TYPE => $this->valueParserFactory->getParserIds(),
				// Use 'datatype' instead!
				ParamValidator::PARAM_DEPRECATED => true,
				ParamValidator::PARAM_REQUIRED => false,
			],
			'values' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_ISMULTI => true,
			],
			'options' => [
				ParamValidator::PARAM_TYPE => 'text',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'validate' => [
				ParamValidator::PARAM_TYPE => 'boolean',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		return [
			'action=wbparsevalue&datatype=string&values=foo|bar' =>
				'apihelp-wbparsevalue-example-1',
			'action=wbparsevalue&datatype=time&values=1994-02-08&options={"precision":9}' =>
				'apihelp-wbparsevalue-example-2',
			'action=wbparsevalue&datatype=time&validate&values=1994-02-08&options={"precision":14}' =>
				'apihelp-wbparsevalue-example-3',
			'action=wbparsevalue&property=P123&validate&values=foo' =>
				'apihelp-wbparsevalue-example-4',
		];
	}

}
