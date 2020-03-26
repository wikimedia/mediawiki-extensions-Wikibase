<?php

namespace Wikibase\Client\Api;

use ApiBase;
use ApiMain;
use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use FormatJson;
use LogicException;
use Parser;
use ParserOptions;
use Title;
use Wikibase\Client\DataAccess\ReferenceFormatterFactory;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\DataModel\Reference;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * An API module to format a serialized Wikibase reference into a human-readable text block.
 *
 * @license GPL-2.0-or-later
 */
class ApiFormatReference extends ApiBase {

	/** @var Parser */
	private $parser;
	/** @var ReferenceFormatterFactory */
	private $referenceFormatterFactory;
	/** @var Deserializer */
	private $referenceDeserializer;

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		Parser $parser,
		ReferenceFormatterFactory $referenceFormatterFactory,
		Deserializer $referenceDeserializer
	) {
		parent::__construct( $mainModule, $moduleName );
		$this->parser = $parser;
		$this->referenceFormatterFactory = $referenceFormatterFactory;
		$this->referenceDeserializer = $referenceDeserializer;
	}

	public function isInternal() {
		// for now, this API is internal; remove this method once we stabilize it
		return true;
	}

	public function execute() {
		$referenceJson = $this->getParameter( 'reference' );
		$referenceJsonStatus = FormatJson::parse( $referenceJson, FormatJson::FORCE_ASSOC );

		if ( !$referenceJsonStatus->isGood() ) {
			$this->dieStatus( $referenceJsonStatus );
		}

		try {
			$reference = $this->referenceDeserializer->deserialize( $referenceJsonStatus->getValue() );
			/** @var Reference $reference */
			'@phan-var Reference $reference';
		} catch ( DeserializationException $e ) {
			$this->dieWithError( 'wikibase-error-deserialize-error' );
		}

		switch ( $this->getParameter( 'style' ) ) {
			case 'internal-data-bridge':
				$referenceFormatter = $this->referenceFormatterFactory->newDataBridgeReferenceFormatter(
					$this,
					$this->getLanguage(),
					new HashUsageAccumulator()
				);
				break;
			default:
				throw new LogicException( 'Unknown style should have been rejected by API framework' );
		}

		$wikitext = $referenceFormatter->formatReference( $reference );

		switch ( $this->getParameter( 'outputformat' ) ) {
			case 'html':
				$parserOptions = ParserOptions::newFromContext( $this );
				$this->parser->parse( $wikitext, Title::makeTitle( 0, 'API' ), $parserOptions );
				$html = $this->parser->getOutput()->getText();
				$this->getResult()->addValue( $this->getModulePath(), 'html', $html );
				break;
			default:
				throw new LogicException( 'Unknown outputformat should have been rejected by API framework' );
		}
	}

	public function getAllowedParams() {
		return [
			'reference' => [
				ParamValidator::PARAM_TYPE => 'text',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'style' => [
				ParamValidator::PARAM_TYPE => [
					'internal-data-bridge',
				],
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'outputformat' => [
				ParamValidator::PARAM_TYPE => [
					'html',
				],
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			],
		];
	}

	protected function getExamplesMessages() {
		$stringReference = FormatJson::encode( [
			'snaks' => [
				'P2' => [ [
					'property' => 'P2',
					'snaktype' => 'value',
					'datavalue' => [ 'type' => 'string', 'value' => 'a string' ],
					'datatype' => 'string',
				] ],
			],
		], true );
		return [
			'action=wbformatreference&reference={"snaks":{}}&style=internal-data-bridge&outputformat=html'
				=> 'apihelp-wbformatreference-example-1',
			"action=wbformatreference&reference=$stringReference&style=internal-data-bridge&outputformat=html"
				=> 'apihelp-wbformatreference-example-2',
		];
	}

}
