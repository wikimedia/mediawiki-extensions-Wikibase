<?php

namespace Wikibase\Client\Api;

use ApiQuery;
use ApiQueryBase;
use Wikibase\Client\Store\DescriptionLookup;

/**
 * Provides a short description of the page in the content language.
 * The description may be taken from an upstream Wikibase instance, or from a parser function in
 * the article wikitext.
 *
 * Arguably this should be a separate extension so that it can be used on wikis without Wikibase
 * as well, but was initially implemented inside Wikibase for speed and convenience (T189154).
 *
 * @license GPL-2.0+
 */
class Description extends ApiQueryBase {

	/**
	 * @var bool Setting to enable local override of descriptions.
	 */
	private $allowLocalShortDesc;

	/**
	 * @var DescriptionLookup
	 */
	private $descriptionLookup;

	/**
	 * @param ApiQuery $query
	 * @param string $moduleName
	 * @param bool $allowLocalShortDesc Whether the wiki allows local descriptions.
	 * @param DescriptionLookup $descriptionLookup
	 */
	public function __construct(
		ApiQuery $query,
		$moduleName,
		$allowLocalShortDesc,
		DescriptionLookup $descriptionLookup
	) {
		parent::__construct( $query, $moduleName, 'desc' );
		$this->allowLocalShortDesc = $allowLocalShortDesc;
		$this->descriptionLookup = $descriptionLookup;
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

		if ( !$this->allowLocalShortDesc ) {
			$sources = [ DescriptionLookup::SOURCE_CENTRAL ];
		} elseif ( $preferSource === DescriptionLookup::SOURCE_LOCAL ) {
			$sources = [ DescriptionLookup::SOURCE_LOCAL, DescriptionLookup::SOURCE_CENTRAL ];
		} else {
			$sources = [ DescriptionLookup::SOURCE_CENTRAL, DescriptionLookup::SOURCE_LOCAL ];
		}
		$descriptions = $this->descriptionLookup->getDescriptions( $titlesByPageId, $sources,
			$actualSources );

		$this->addDataToResponse( array_keys( $titlesByPageId ), $descriptions,
			array_filter( $actualSources ), $continue );
	}

	/**
	 * @param int[] $pageIds Page IDs, in the same order as returned by the ApiPageSet.
	 * @param string[] $descriptionsByPageId Descriptions as an associative array of
	 *   page ID => description in the content language.
	 * @param string[] $sourcesByPageId Identifies where each source came from;
	 *   an associative array of page ID => DescriptionLookup::SOURCE_*.
	 * @param int $continue The API request is being continued from this position.
	 */
	private function addDataToResponse(
		array $pageIds,
		array $descriptionsByPageId,
		array $sourcesByPageId,
		$continue
	) {
		$result = $this->getResult();
		$i = 0;
		$fit = true;
		foreach ( $pageIds as $pageId ) {
			if ( !isset( $descriptionsByPageId[$pageId] ) ) {
				continue;
			}
			$path = [ 'query', 'pages', $pageId ];
			$fit = $result->addValue( $path, 'description', $descriptionsByPageId[$pageId] )
				&& $result->addValue( $path, 'descriptionsource', $sourcesByPageId[$pageId] );
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
					DescriptionLookup::SOURCE_LOCAL,
					DescriptionLookup::SOURCE_CENTRAL,
				],
				self::PARAM_DFLT => DescriptionLookup::SOURCE_LOCAL,
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
