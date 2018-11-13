<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use DOMElement;
use DOMNode;
use IBufferingStatsdDataFactory;
use RemexHtml\DOM\DOMBuilder;
use RemexHtml\HTMLData;
use RemexHtml\Serializer\HtmlFormatter;
use RemexHtml\Tokenizer\Tokenizer;
use RemexHtml\TreeBuilder\Dispatcher;
use RemexHtml\TreeBuilder\TreeBuilder;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * API module for formatting a set of entity IDs.
 *
 * @license GPL-2.0-or-later
 */
class FormatEntities extends ApiBase {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityIdFormatterFactory
	 */
	private $entityIdFormatterFactory;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var IBufferingStatsdDataFactory
	 */
	private $dataFactory;

	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		EntityIdParser $entityIdParser,
		EntityIdFormatterFactory $entityIdFormatterFactory,
		ResultBuilder $resultBuilder,
		ApiErrorReporter $errorReporter,
		IBufferingStatsdDataFactory $dataFactory
	) {
		parent::__construct( $mainModule, $moduleName, '' );

		$this->entityIdParser = $entityIdParser;
		$this->entityIdFormatterFactory = $entityIdFormatterFactory;
		$this->resultBuilder = $resultBuilder;
		$this->errorReporter = $errorReporter;
		$this->dataFactory = $dataFactory;
	}

	public function execute() {
		$this->getMain()->setCacheMode( 'public' );

		$language = $this->getMain()->getLanguage();
		$entityIdFormatter = $this->entityIdFormatterFactory->getEntityIdFormatter( $language );

		$params = $this->extractRequestParams();
		$entityIds = $this->getEntityIdsFromIdParam( $params );

		$this->dataFactory->updateCount(
			'wikibase.repo.api.formatentities.entities',
			count( $entityIds )
		);

		foreach ( $entityIds as $entityId ) {
			$formatted = $entityIdFormatter->formatEntityId( $entityId );
			$formatted = self::makeLinksAbsolute( $formatted );
			$this->resultBuilder->setValue( $this->getModuleName(), $entityId->getSerialization(), $formatted );
		}

		$this->resultBuilder->markSuccess( 1 );
	}

	/**
	 * @param array $params
	 *
	 * @return EntityId[]
	 */
	private function getEntityIdsFromIdParam( array $params ) {
		$ids = [];
		foreach ( $params['ids'] as $id ) {
			try {
				$ids[] = $this->entityIdParser->parse( $id );
			} catch ( EntityIdParsingException $e ) {
				$this->errorReporter->dieWithError(
					[ 'wikibase-api-no-such-entity', $id ],
					'no-such-entity',
					0,
					[ 'id' => $id ]
				);
			}
		}
		return $ids;
	}

	/**
	 * Make the `href=""` attributes of `<a>` elements in an HTML snippet absolute.
	 * URLs are expanded using {@link wfExpandUrl}.
	 *
	 * @param string $html
	 * @return string
	 */
	private static function makeLinksAbsolute( $html ) {
		$domBuilder = new DOMBuilder();
		$treeBuilder = new TreeBuilder( $domBuilder, [] );
		$dispatcher = new Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer( $dispatcher, $html, [] );

		$tokenizer->execute( [
			'fragmentNamespace' => HTMLData::NS_HTML,
			'fragmentName' => 'body',
		] );
		/** @var DOMNode $node */
		$node = $domBuilder->getFragment();
		self::makeLinksAbsoluteDOM( $node );

		$formatter = new HtmlFormatter();
		$absoluteHtml = $formatter->formatDOMNode( $node );
		if ( substr( $absoluteHtml, 0, 6 ) === '<html>' ) {
			$absoluteHtml = substr( $absoluteHtml, 6 );
		}
		if ( substr( $absoluteHtml, -7 ) === '</html>' ) {
			$absoluteHtml = substr( $absoluteHtml, 0, -7 );
		}
		return $absoluteHtml;
	}

	private static function makeLinksAbsoluteDOM( DOMNode $node ) {
		if (
			$node instanceof DOMElement &&
			$node->nodeName === 'a' &&
			$node->hasAttribute( 'href' )
		) {
			$href = wfExpandUrl( $node->getAttribute( 'href' ), PROTO_CANONICAL );
			$node->setAttribute( 'href', $href );
		}
		if ( $node->hasChildNodes() ) {
			for ( $index = 0; $index < $node->childNodes->length; $index ++ ) {
				self::makeLinksAbsoluteDOM( $node->childNodes[$index] );
			}
		}
	}

	protected function getAllowedParams() {
		return [
			'ids' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_ISMULTI => true,
			],
		];
	}

	protected function getExamplesMessages() {
		$exampleMessages = [
			'action=wbformatentities&ids=Q2'
				=> 'apihelp-wbformatentities-example-1',
			'action=wbformatentities&ids=Q2|P2'
				=> 'apihelp-wbformatentities-example-2',
		];

		if ( \ExtensionRegistry::getInstance()->isLoaded( 'WikibaseLexeme' ) ) {
			$exampleMessages = array_merge( $exampleMessages, [
				'action=wbformatentities&ids=Q2|P2|L2'
					=> 'apihelp-wbformatentities-example-3',
			] );
		}

		$exampleMessages = array_merge( $exampleMessages, [
			'action=wbformatentities&ids=Q2|Q3|Q4&uselang=fr'
				=> 'apihelp-wbformatentities-example-4',
		] );

		return $exampleMessages;
	}

}
