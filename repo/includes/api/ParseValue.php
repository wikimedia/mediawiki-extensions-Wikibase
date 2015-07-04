<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use ApiResult;
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
			new ValueParserFactory( $GLOBALS['wgValueParsers'] ),
			$wikibaseRepo->getExceptionLocalizer(),
			$apiHelperFactory->getErrorReporter( $this )
		);
	}

	public function setServices(
		ValueParserFactory $valueParserFactory,
		ExceptionLocalizer $exceptionLocalizer,
		ApiErrorReporter $errorReporter
	) {
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

		try {
			$parser = $this->valueParserFactory->newParser( $params['parser'], $options );
		} catch ( OutOfBoundsException $ex ) {
			throw new LogicException( 'Could not obtain a ValueParser instance' );
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
		}
		catch ( ParseException $parseError ) {
			$this->addParseErrorToResult( $result, $parseError );
			return $result;
		}

		if ( $parseResult instanceof DataValue ) {
			$result['value'] = $parseResult->getArrayValue();
			$result['type'] = $parseResult->getType();
		}
		else {
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
	 * @param string $optionsParam
	 *
	 * @return ParserOptions
	 */
	private function getOptionsObject( $optionsParam ) {
		$parserOptions = new ParserOptions();
		$parserOptions->setOption( ValueParser::OPT_LANG, $this->getLanguage()->getCode() );

		if ( $optionsParam !== null && $optionsParam !== '' ) {
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
			'parser' => array(
				ApiBase::PARAM_TYPE => $this->valueParserFactory->getParserIds(),
				ApiBase::PARAM_REQUIRED => true,
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
