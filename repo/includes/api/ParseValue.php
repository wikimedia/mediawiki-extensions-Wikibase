<?php

namespace Wikibase\Api;

use ApiBase;
use ApiResult;
use DataValues\DataValue;
use LogicException;
use OutOfBoundsException;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\Repo\ValueParserFactory;
use Wikibase\Repo\ValueValidatorFactory;

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
class ParseValue extends ApiWikibase {

	/**
	 * @var null|ValueParserFactory
	 */
	private $parserFactory = null;

	/**
	 * @var null|ValueValidatorFactory
	 */
	private $validatorFactory = null;

	/**
	 * @return ValueParserFactory
	 */
	private function getParserFactory() {
		if ( $this->parserFactory === null ) {
			$this->parserFactory = new ValueParserFactory( $GLOBALS['wgValueParsers'] );
		}

		return $this->parserFactory;
	}

	/**
	 * @return ValueValidatorFactory
	 */
	private function getValidatorFactory() {
		if ( $this->validatorFactory === null ) {
			$this->validatorFactory = new ValueValidatorFactory( $GLOBALS['wgValueValidators'] );
		}

		return $this->validatorFactory;
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.1
	 */
	public function execute() {
		$parser = $this->getParser();
		$validator = $this->getValidator();

		$results = array();

		$params = $this->extractRequestParams();

		foreach ( $params['values'] as $value ) {
			$results[] = $this->parseStringValue( $parser, $value );
		}

		if ( $validator !== null ) {
			foreach ( $results as &$result ) {
				$this->validateParsedValue( $validator, $result );
			}
		}

		$this->outputResults( $this->formatResultsForOutput( $results ) );
	}

	private function formatResultsForOutput( $results ) {
		foreach ( $results as &$result ) {
			if ( array_key_exists( 'parsedvalue', $result ) ) {
				if ( $result['parsedvalue'] instanceof DataValue ) {
					$result['value'] = $result['parsedvalue']->getArrayValue();
					$result['type'] = $result['parsedvalue']->getType();
				} else {
					$result['value'] = $result['parsedvalue'];
				}
				unset( $result['parsedvalue'] );
			}
		}
		return $results;
	}

	/**
	 * @return ValueParser
	 * @throws LogicException
	 */
	private function getParser() {
		$params = $this->extractRequestParams();

		$options = $this->getParserOptionsObject( $params['options'] );

		try {
			$parser = $this->getParserFactory()->newParser( $params['parser'], $options );
		} catch ( OutOfBoundsException $ex ) {
			throw new LogicException( 'Could not obtain a ValueParser instance' );
		}

		return $parser;
	}

	/**
	 * @return ValueValidator|null
	 */
	private function getValidator() {
		$params = $this->extractRequestParams();

		try {
			$validator = $this->getValidatorFactory()->newValidator( $params['validator'] );
		} catch ( OutOfBoundsException $ex ) {
			return null;
		}

		return $validator;
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
			$result['parsedvalue'] = $parser->parse( $value );
		}
		catch ( ParseException $parseError ) {
			$this->addParseErrorToResult( $result, $parseError );
		}

		return $result;
	}

	private function validateParsedValue( ValueValidator $validator, &$result ) {
		if ( array_key_exists( 'parsedvalue', $result ) ) {

			if ( $result['parsedvalue'] instanceof DataValue ) {
				$validationResult = $validator->validate( $result['parsedvalue']->getValue() );
			} else {
				$validationResult = $validator->validate( $result['parsedvalue'] );
			}
			$this->addValidationResultToResult( $result, $validationResult );

		} else {
			$result['valid'] = false;
		}
	}

	private function addValidationResultToResult( array &$result, Result $validationResult ) {
		$isValid = $validationResult->isValid();
		if ( $isValid ) {
			$result['valid'] = true;
		} else {
			$result['valid'] = false;
			$errors = $validationResult->getErrors();
			foreach ( $errors as $key => $error ) {
				$result['validation-info'][$key]['code'] = $error->getCode();
				$result['validation-info'][$key]['text'] = $error->getText();
			}
		}
	}

	private function addParseErrorToResult( array &$result, ParseException $parseError ) {
		$result['error'] = get_class( $parseError );

		$result['error-info'] = $parseError->getMessage();
		$result['expected-format'] = $parseError->getExpectedFormat();

		$status = $this->getExceptionStatus( $parseError );
		$this->getErrorReporter()->addStatusToResult( $status, $result );
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
	 * @param string $optionsParam
	 *
	 * @return ParserOptions
	 */
	private function getParserOptionsObject( $optionsParam ) {
		$parserOptions = new ParserOptions();
		$parserOptions->setOption( ValueParser::OPT_LANG, $this->getLanguage()->getCode() );

		if ( $optionsParam !== null && $optionsParam !== '' ) {
			$options = json_decode( $optionsParam, true );

			if ( !is_array( $options ) ) {
				$this->dieError( 'Malformed options parameter', 'malformed-options' );
			}

			foreach ( $options as $name => $value ) {
				$parserOptions->setOption( $name, $value );
			}
		}

		return $parserOptions;
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array(
			'parser' => array(
				ApiBase::PARAM_TYPE => $this->getParserFactory()->getParserIds(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'validator' => array(
				ApiBase::PARAM_TYPE => $this->getValidatorFactory()->getValidatorIds(),
				ApiBase::PARAM_REQUIRED => false,
			),
			'values' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_ISMULTI => true,
			),
			'options' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
			),
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbparsevalue&parser=null&values=foo|bar' =>
				'apihelp-wbparsevalue-example-1',
			'action=wbparsevalue&parser=time&values=1994-02-08&options={"precision":9}' =>
				'apihelp-wbparsevalue-example-2',
		);
	}

}
