<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use IBufferingStatsdDataFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\View\EntityIdFormatterFactory;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Serializer\HtmlFormatter;
use Wikimedia\RemexHtml\Serializer\Serializer;
use Wikimedia\RemexHtml\Serializer\SerializerNode;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder\Dispatcher;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;

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
		string $moduleName,
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

	public static function factory(
		ApiMain $apiMain,
		string $moduleName,
		IBufferingStatsdDataFactory $dataFactory,
		ApiHelperFactory $apiHelperFactory,
		EntityIdFormatterFactory $entityIdFormatterFactory,
		EntityIdParser $entityIdParser
	): self {
		return new self(
			$apiMain,
			$moduleName,
			$entityIdParser,
			$entityIdFormatterFactory,
			$apiHelperFactory->getResultBuilder( $apiMain ),
			$apiHelperFactory->getErrorReporter( $apiMain ),
			$dataFactory
		);
	}

	public function execute(): void {
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
	private function getEntityIdsFromIdParam( array $params ): array {
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
	private static function makeLinksAbsolute( string $html ): string {
		$formatter = new class extends HtmlFormatter {

			public function element( SerializerNode $parent, SerializerNode $node, $contents ) {
				if ( $node->namespace === HTMLData::NS_HTML
					&& $node->name === 'a'
					&& isset( $node->attrs['href'] )
				) {
					$node = clone $node;
					$node->attrs = clone $node->attrs;
					$node->attrs['href'] = wfExpandUrl( $node->attrs['href'], PROTO_CANONICAL );
				}
				return parent::element( $parent, $node, $contents );
			}

			public function startDocument( $fragmentNamespace, $fragmentName ) {
				return '';
			}

		};

		$serializer = new Serializer( $formatter );
		$treeBuilder = new TreeBuilder( $serializer );
		$dispatcher = new Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer( $dispatcher, $html );

		$tokenizer->execute( [
			'fragmentNamespace' => HTMLData::NS_HTML,
			'fragmentName' => 'body',
		] );

		return $serializer->getResult();
	}

	protected function getAllowedParams(): array {
		return [
			'ids' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => true,
			],
		];
	}

	protected function getExamplesMessages(): array {
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
