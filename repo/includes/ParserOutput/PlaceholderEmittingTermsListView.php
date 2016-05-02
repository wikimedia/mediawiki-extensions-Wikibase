<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\View\TermsListView;

/**
 * Generates a TextInjector marker instead of a terms list
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class PlaceholderEmittingTermsListView implements TermsListView {

	/**
	 * @var TextInjector
	 */
	private $textInjector;

	/**
	 * @param TextInjector $textInjector
	 */
	public function __construct(
		TextInjector $textInjector
	) {
		$this->textInjector = $textInjector;
	}

	/**
	 * @param LabelsProvider $labelsProvider
	 * @param DescriptionsProvider $descriptionsProvider
	 * @param AliasesProvider|null $aliasesProvider
	 * @param string[] $languageCodes The languages the user requested to be shown
	 *
	 * @return string HTML
	 */
	public function getHtml(
		LabelsProvider $labelsProvider,
		DescriptionsProvider $descriptionsProvider,
		AliasesProvider $aliasesProvider = null,
		array $languageCodes
	) {
		return $this->textInjector->newMarker( 'termbox' );
	}

}
