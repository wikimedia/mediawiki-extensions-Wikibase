<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use IBufferingStatsdDataFactory;
use RemexHtml\Serializer\HtmlFormatter;
use RemexHtml\Serializer\Serializer;
use RemexHtml\Serializer\SerializerNode;
use RemexHtml\Tokenizer\Attributes;
use RemexHtml\Tokenizer\PlainAttributes;
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
			$formatted = self::makeHrefAbsolute( $formatted );
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
	 * Make the `href=""` attribute of an `<a>` element in an HTML snippet absolute.
	 *
	 * Only adjusts a single `<a>` element,
	 * and there must not be any tags before the opening `<a>` tag
	 * (though there may be anything after it).
	 * The `href` attribute is expanded using {@link wfExpandUrl},
	 * and all other attributes are left untouched.
	 *
	 * @param string $html
	 * @return string
	 */
	private static function makeHrefAbsolute( $html ) {
		$formatter = new class extends HtmlFormatter {

			public function startDocument( $fragmentNamespace, $fragmentName ) {
				// don’t emit <!DOCTYPE>
			}

			public function element( SerializerNode $parent, SerializerNode $node, $contents ) {
				if ( $node->name === 'a' ) {
					/** @var Attributes $attributes */
					$attributes = $node->attrs;
					/** @var string[] $attributeValues */
					$attributeValues = $attributes->getValues();
					if ( array_key_exists( 'href', $attributeValues ) ) {
						$attributeValues['href'] = wfExpandUrl( $attributeValues['href'], PROTO_CANONICAL );
						$attributes = new PlainAttributes( $attributeValues );
					}
					$node = new SerializerNode(
						$node->id,
						$node->parentId,
						$node->namespace,
						$node->name,
						$attributes,
						$node->snData
					);
				} elseif ( $node->name === 'html' || $node->name === 'head' || $node->name === 'body' ) {
					return $contents; // don’t wrap in <html>/<head>/<body> tags
				}

				return parent::element( $parent, $node, $contents );
			}

		};
		$serializer = new Serializer( $formatter );
		$treeBuilder = new TreeBuilder( $serializer, [] );
		$dispatcher = new Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer( $dispatcher, $html, [] );
		$tokenizer->execute();
		return $serializer->getResult();
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
