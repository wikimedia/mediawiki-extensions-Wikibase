<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use IBufferingStatsdDataFactory;
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
	 * This function is only public for testing purposes.
	 * It really shouldn’t exist at all,
	 * and you definitely shouldn’t use it anywhere else.
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
	public static function makeHrefAbsolute( $html ) {
		$attrName = '[^[:space:][:cntrl:]' . '"' . "'" . '>/=' . ']+';
		$unquotedAttributeValue = '[^[:space:]' . '"' . "'" . '=><`' . ']*';
		$singleQuotedAttributeValue = "'[^']*'";
		$doubleQuotedAttributeValue = '"[^"]*"';
		$attr = $attrName . '(?:\s*=\s*(?:' .
			$unquotedAttributeValue . '|' .
			$singleQuotedAttributeValue . '|' .
			$doubleQuotedAttributeValue . '))?';
		$hrefAttr = 'href\s*=\s*(' .
			$unquotedAttributeValue . '|' .
			$singleQuotedAttributeValue . '|' .
			$doubleQuotedAttributeValue . ')';

		if ( !preg_match( '!(^[^<]*<a(?:\s+' . $attr . ')*\s+)' . $hrefAttr . '((?:\s+' . $attr . ')*>.*$)!', $html, $matches ) ) {
			return $html;
		}

		$beginning = $matches[1];
		$hrefValue = $matches[2];
		$end = $matches[3];

		if ( $hrefValue[0] === '"' ) {
			$href = substr( $hrefValue, 1, -1 );
			$quote = '"';
		} elseif ( $hrefValue[0] === "'" ) {
			$href = substr( $hrefValue, 1, -1 );
			$quote = "'";
		} else {
			$href = $hrefValue;
			$quote = '';
		}

		$href = wfExpandUrl( $href, PROTO_CANONICAL );

		return $beginning . 'href=' . $quote . $href . $quote . $end;
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

		return $exampleMessages;
	}

}
