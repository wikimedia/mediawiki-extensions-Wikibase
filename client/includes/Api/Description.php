<?php

namespace Wikibase\Client\Api;

use ApiQuery;
use ApiQueryBase;
use Language;
use PageProps;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Store\EntityIdLookup;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;

/**
 * Provides a short description of the page in the content language.
 * The description may be taken from an upstream Wikibase instance, or from a parser function in
 * the article wikitext.
 *
 * @fixme There should be a way to provide descriptions without installing all of Wikibase,
 *   possibly by moving this API into a separate extension.
 *
 * @license GPL-2.0+
 */
class Description extends ApiQueryBase {

	/**
	 * Local description, in the form of a {{SHORTDESC:...}} parser function.
	 */
	const SOURCE_LOCAL = 'local';

	/**
	 * Central description, from a associated Wikibase repo installation.
	 */
	const SOUCE_CENTRAL = 'central';

	/**
	 * @var bool Setting to enable local override of descriptions.
	 */
	private $allowLocalDescription;

	/**
	 * @var Language
	 */
	private $contentLanguage;

	/**
	 * @var EntityIdLookup
	 */
	private $idLookup;

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @param bool $allowLocalDescription Whether the wiki allows local descriptions.
	 * @param Language $contentLanguage
	 * @param EntityIdLookup $idLookup
	 * @param TermIndex $termIndex
	 * @param ApiQuery $query
	 * @param string $moduleName
	 */
	public function __construct(
		$allowLocalDescription,
		Language $contentLanguage,
		EntityIdLookup $idLookup,
		TermIndex $termIndex,
		ApiQuery $query,
		$moduleName
	) {
		parent::__construct( $query, $moduleName, 'desc' );
		$this->allowLocalDescription = $allowLocalDescription;
		$this->contentLanguage = $contentLanguage;
		$this->idLookup = $idLookup;
		$this->termIndex = $termIndex;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$continue = $this->getParameter( 'continue' );
		$preferSource = $this->getParameter( 'prefersource' );

		$titlesByPageId = $this->getPageSet()->getGoodTitles();
		// Just in case we are dealing with titles from some very fast generator,
		// apply some limits as a sanity check.
		$limit = $this->getMain()->canApiHighLimits() ? self::LIMIT_BIG2 : self::LIMIT_BIG1;
		if ( $continue + $limit < count( $titlesByPageId ) ) {
			$this->setContinueEnumParameter( 'continue', $continue + $limit );
		}
		$titlesByPageId = array_slice( $titlesByPageId, $continue, $limit, true );

		if ( $this->allowLocalDescription && $preferSource === self::SOURCE_LOCAL ) {
			$localDescriptionsByPageId = $this->getLocalDescriptions( $titlesByPageId );
			$centralDescriptionsByPageId = $this->getCentralDescriptions(
				array_diff_key( $titlesByPageId, $localDescriptionsByPageId ) );
		} else {
			$centralDescriptionsByPageId = $this->getCentralDescriptions( $titlesByPageId );
			if ( $this->allowLocalDescription ) {
				$localDescriptionsByPageId = $this->getLocalDescriptions(
					array_diff_key( $titlesByPageId, $centralDescriptionsByPageId ) );
			} else {
				$localDescriptionsByPageId = [];
			}
		}

		$this->addDescriptionsToResult( array_keys( $titlesByPageId ),
			$localDescriptionsByPageId, $centralDescriptionsByPageId, $continue );
	}

	/**
	 * @param Title[] $titlesByPageId
	 *
	 * @return string[] Associative array of page ID => description.
	 */
	private function getLocalDescriptions( array $titlesByPageId ) {
		if ( !$titlesByPageId ) {
			return [];
		}
		return PageProps::getInstance()->getProperties( $titlesByPageId, 'wikibase-shortdesc' );
	}

	/**
	 * @param Title[] $titlesByPageId
	 *
	 * @return string[] Associative array of page ID => description.
	 */
	private function getCentralDescriptions( array $titlesByPageId ) {
		if ( !$titlesByPageId ) {
			return [];
		}
		$entityIdsByPageId = $this->idLookup->getEntityIds( $titlesByPageId );
		$termIndexEntries = $this->termIndex->getTermsOfEntities( $entityIdsByPageId,
			[ TermIndexEntry::TYPE_DESCRIPTION ], [ $this->contentLanguage->getCode() ] );

		$pageIdsByEntityId = array_flip( array_map( function ( EntityId $entityId ) {
			return $entityId->getSerialization();
		}, $entityIdsByPageId ) );
		$descriptionsByPageId = [];
		foreach ( $termIndexEntries as $termIndexEntry ) {
			$pageId = $pageIdsByEntityId[$termIndexEntry->getEntityId()->getSerialization()];
			$descriptionsByPageId[$pageId] = $termIndexEntry->getText();
		}
		return $descriptionsByPageId;
	}

	/**
	 * @param int[] $pageIds Page IDs, in the same order as returned by the ApiPageSet.
	 * @param string[] $localDescriptionsByPageId Descriptions from local wikitext, as an
	 *   associative array of page ID => description in the content language.
	 * @param string[] $centralDescriptionsByPageId Descriptions from the central Wikibase repo,
	 *   as an associative array of page ID => description in the content language.
	 * @param int $continue The API request is being continued from this position.
	 */
	private function addDescriptionsToResult(
		array $pageIds,
		array $localDescriptionsByPageId,
		array $centralDescriptionsByPageId,
		$continue
	) {
		$result = $this->getResult();
		$i = 0;
		$fit = true;
		foreach ( $pageIds as $pageId ) {
			$path = [ 'query', 'pages', $pageId ];
			if ( array_key_exists( $pageId, $localDescriptionsByPageId ) ) {
				$fit = $result->addValue( $path, 'description', $localDescriptionsByPageId[$pageId] )
					&& $result->addValue( $path, 'descriptionsource', 'local' );
			} elseif ( array_key_exists( $pageId, $centralDescriptionsByPageId ) ) {
				$fit = $result->addValue( $path, 'description', $centralDescriptionsByPageId[$pageId] )
					   && $result->addValue( $path, 'descriptionsource', 'central' );
			}
			if ( !$fit ) {
				$this->setContinueEnumParameter( 'continue', $continue + $i );
				break;
			}
			$i++;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getCacheMode( $params ) {
		return 'public';
	}

	/**
	 * @inheritDoc
	 */
	public function isInternal() {
		// new API, not stable yet
		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams() {
		return [
			'continue' => [
				self::PARAM_HELP_MSG => 'api-help-param-continue',
				self::PARAM_TYPE => 'integer',
				self::PARAM_DFLT => 0,
			],
			'prefersource' => [
				// Designating 'local' as the preferred source is allowed even if the wiki does
				// not actually allow local descriptions, to make clients' life easier.
				self::PARAM_TYPE => [
					self::SOURCE_LOCAL,
					self::SOUCE_CENTRAL,
				],
				self::PARAM_DFLT => self::SOURCE_LOCAL,
				self::PARAM_HELP_MSG_PER_VALUE => [],
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=query&prop=description&titles=London'
			=> 'apihelp-query+description-example',
			'action=query&prop=description&titles=London&descprefersource=central'
			=> 'apihelp-query+description-example-central',
		];
	}

}
