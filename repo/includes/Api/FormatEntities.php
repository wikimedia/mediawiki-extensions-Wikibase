<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use LogicException;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\View\EntityIdFormatterFactory;
use Wikimedia\Assert\Assert;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Serializer\HtmlFormatter;
use Wikimedia\RemexHtml\Serializer\Serializer;
use Wikimedia\RemexHtml\Serializer\SerializerNode;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder\Dispatcher;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;
use Wikimedia\Stats\StatsFactory;

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
	private $entityIdHtmlFormatterFactory;

	/**
	 * @var EntityIdFormatterFactory
	 */
	private $entityIdTextFormatterFactory;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var StatsFactory
	 */
	private $statsFactory;

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		EntityIdParser $entityIdParser,
		EntityIdFormatterFactory $entityIdHtmlFormatterFactory,
		EntityIdFormatterFactory $entityIdTextFormatterFactory,
		ResultBuilder $resultBuilder,
		ApiErrorReporter $errorReporter,
		StatsFactory $statsFactory
	) {
		parent::__construct( $mainModule, $moduleName, '' );
		$htmlFormat = $entityIdHtmlFormatterFactory->getOutputFormat();
		Assert::parameter(
			$htmlFormat === SnakFormatter::FORMAT_HTML,
			'$entityIdHtmlFormatterFactory',
			'must format to ' . SnakFormatter::FORMAT_HTML . ', not ' . $htmlFormat
		);
		$textFormat = $entityIdTextFormatterFactory->getOutputFormat();
		Assert::parameter(
			$textFormat === SnakFormatter::FORMAT_PLAIN,
			'$entityIdTextFormatterFactory',
			'must format to ' . SnakFormatter::FORMAT_PLAIN . ', not ' . $textFormat
		);

		$this->entityIdParser = $entityIdParser;
		$this->entityIdHtmlFormatterFactory = $entityIdHtmlFormatterFactory;
		$this->entityIdTextFormatterFactory = $entityIdTextFormatterFactory;
		$this->resultBuilder = $resultBuilder;
		$this->errorReporter = $errorReporter;
		$this->statsFactory = $statsFactory->withComponent( 'WikibaseRepo' );
	}

	public static function factory(
		ApiMain $apiMain,
		string $moduleName,
		StatsFactory $statsFactory,
		ApiHelperFactory $apiHelperFactory,
		EntityIdFormatterFactory $entityIdHtmlFormatterFactory,
		EntityIdFormatterFactory $entityIdTextFormatterFactory,
		EntityIdParser $entityIdParser
	): self {
		return new self(
			$apiMain,
			$moduleName,
			$entityIdParser,
			$entityIdHtmlFormatterFactory,
			$entityIdTextFormatterFactory,
			$apiHelperFactory->getResultBuilder( $apiMain ),
			$apiHelperFactory->getErrorReporter( $apiMain ),
			$statsFactory
		);
	}

	public function execute(): void {
		$this->getMain()->setCacheMode( 'public' );

		$language = $this->getMain()->getLanguage();
		$params = $this->extractRequestParams();

		if ( $params['generate'] === SnakFormatter::FORMAT_HTML ) {
			$entityIdFormatterFactory = $this->entityIdHtmlFormatterFactory;
		} elseif ( $params['generate'] === SnakFormatter::FORMAT_PLAIN ) {
			$entityIdFormatterFactory = $this->entityIdTextFormatterFactory;
		} else {
			throw new LogicException( 'Unexpected "generate" parameter value: ' . $params['generate'] );
		}
		$entityIdFormatter = $entityIdFormatterFactory->getEntityIdFormatter( $language );

		$entityIds = $this->getEntityIdsFromIdParam( $params );

		$metric = $this->statsFactory->getCounter( 'formatentities_entities_total' );
		$metric->copyToStatsdAt(
			'wikibase.repo.api.formatentities.entities'
		)->incrementBy( count( $entityIds ) );

		foreach ( $entityIds as $entityId ) {
			$formatted = $entityIdFormatter->formatEntityId( $entityId );
			if ( $params['generate'] === SnakFormatter::FORMAT_HTML ) {
				$formatted = self::makeLinksAbsolute( $formatted );
			}
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
	 * URLs are expanded using {@link \MediaWiki\Utils\UrlUtils::expand}.
	 *
	 * @param string $html
	 * @return string
	 */
	private static function makeLinksAbsolute( string $html ): string {
		$formatter = new class extends HtmlFormatter {

			/**
			 * @inheritDoc
			 */
			public function element( SerializerNode $parent, SerializerNode $node, $contents ) {
				if ( $node->namespace === HTMLData::NS_HTML
					&& $node->name === 'a'
					&& isset( $node->attrs['href'] )
				) {
					$urlUtils = MediaWikiServices::getInstance()->getUrlUtils();

					$node = clone $node;
					$node->attrs = clone $node->attrs;
					$node->attrs['href'] = $urlUtils->expand( $node->attrs['href'], PROTO_CANONICAL );
				}
				return parent::element( $parent, $node, $contents );
			}

			/**
			 * @inheritDoc
			 */
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
			'generate' => [
				ParamValidator::PARAM_TYPE => [
					SnakFormatter::FORMAT_PLAIN,
					SnakFormatter::FORMAT_HTML,
				],
				ParamValidator::PARAM_DEFAULT => SnakFormatter::FORMAT_HTML,
				ParamValidator::PARAM_REQUIRED => false,
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

		if ( ExtensionRegistry::getInstance()->isLoaded( 'WikibaseLexeme' ) ) {
			$exampleMessages = array_merge( $exampleMessages, [
				'action=wbformatentities&ids=Q2|P2|L2'
					=> 'apihelp-wbformatentities-example-3',
			] );
		}

		$exampleMessages = array_merge( $exampleMessages, [
			'action=wbformatentities&ids=Q2|Q3|Q4&uselang=fr'
				=> 'apihelp-wbformatentities-example-4',
			'action=wbformatentities&ids=Q2|Q3|Q4|P2&generate=text/plain'
				=> 'apihelp-wbformatentities-example-5',
		] );

		return $exampleMessages;
	}

}
