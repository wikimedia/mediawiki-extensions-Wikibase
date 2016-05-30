<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use MWException;
use RuntimeException;
use User;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\TermsListView;
use Wikibase\View\Template\TemplateFactory;

/**
 * Utility for expanding placeholders left in the HTML
 *
 * This is used to inject any non-cacheable information into the HTML
 * that was cached as part of the ParserOutput.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class EntityViewPlaceholderExpander {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var LabelsProvider
	 */
	private $labelsProvider;

	/**
	 * @var DescriptionsProvider
	 */
	private $descriptionsProvider;

	/**
	 * @var AliasesProvider|null
	 */
	private $aliasesProvider;

	/**
	 * @var string[]
	 */
	private $termsLanguages;

	/**
	 * @var LanguageDirectionalityLookup
	 */
	private $languageDirectionalityLookup;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @var string[]
	 */
	private $termsListItems;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param User $user the current user
	 * @param LabelsProvider $labelsProvider
	 * @param DescriptionsProvider $descriptionsProvider
	 * @param AliasesProvider|null $aliasesProvider
	 * @param string[] $termsLanguages
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param LanguageNameLookup $languageNameLookup
	 * @param LocalizedTextProvider $textProvider
	 * @param string[] $termsListItems
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		User $user,
		LabelsProvider $labelsProvider,
		DescriptionsProvider $descriptionsProvider,
		AliasesProvider $aliasesProvider = null,
		array $termsLanguages,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		LanguageNameLookup $languageNameLookup,
		LocalizedTextProvider $textProvider,
		array $termsListItems = array()
	) {
		$this->user = $user;
		$this->labelsProvider = $labelsProvider;
		$this->descriptionsProvider = $descriptionsProvider;
		$this->aliasesProvider = $aliasesProvider;
		$this->templateFactory = $templateFactory;
		$this->termsLanguages = $termsLanguages;
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
		$this->languageNameLookup = $languageNameLookup;
		$this->textProvider = $textProvider;
		$this->termsListItems = $termsListItems;
	}

	/**
	 * Callback for expanding placeholders to HTML,
	 * for use as a callback passed to with TextInjector::inject().
	 *
	 * @note This delegates to expandPlaceholder, which encapsulates knowledge about
	 * the meaning of each placeholder name, as used by EntityView.
	 *
	 * @param string $name the name (or kind) of placeholder; determines how the expansion is done.
	 * @param mixed|null $data Additional data that should be passed to the replacing function
	 *
	 * @return string HTML to be substituted for the placeholder in the output.
	 */
	public function getHtmlForPlaceholder( $name, $data = null ) {
		try {
			return $this->expandPlaceholder( $name, $data );
		} catch ( MWException $ex ) {
			wfWarn( "Expansion of $name failed: " . $ex->getMessage() );
		} catch ( RuntimeException $ex ) {
			wfWarn( "Expansion of $name failed: " . $ex->getMessage() );
		}

		return false;
	}

	/**
	 * Dispatch the expansion of placeholders based on the name.
	 *
	 * @note This encodes knowledge about which placeholders are used with what intended meaning.
	 *
	 * @param string $name
	 * @param mixed|null $data Additional data that should be passed to the replacing function
	 *
	 * @return string
	 */
	protected function expandPlaceholder( $name, $data ) {
		switch ( $name ) {
			case 'termbox':
				return $this->renderTermBox( $data );
			case 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class':
				return $this->isInitiallyCollapsed() ? 'wikibase-initially-collapsed' : '';
			default:
				wfWarn( "Unknown placeholder: $name" );
				return '(((' . htmlspecialchars( $name ) . ')))';
		}
	}

	/**
	 * @return bool If the terms list should be initially collapsed for the current user.
	 */
	private function isInitiallyCollapsed() {
		$name = 'wikibase-entitytermsview-showEntitytermslistview';

		if ( $this->user->isAnon() ) {
			return isset( $_COOKIE[$name] ) && $_COOKIE[$name] === 'false';
		} else {
			return !$this->user->getOption( $name, true );
		}
	}

	/**
	 * Generates HTML of the term box, to be injected into the entity page.
	 *
	 * @param string[]|null $termsListItems An array of pre-rendered terms list HTML snippets per language code
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function renderTermBox( array $termsListItems = null ) {

		if ( $termsListItems === null ) {
			$termsListItems = $this->termsListItems;
		}
		$termsListView = new TermsListView(
			$this->templateFactory,
			$this->languageNameLookup,
			$this->textProvider,
			$this->languageDirectionalityLookup
		);

		$contentHtml = '';
		foreach ( $this->termsLanguages as $languageCode ) {
			if ( isset( $termsListItems[ $languageCode ] ) ) {
				$contentHtml .= $termsListItems[ $languageCode ];
			} else {
				$contentHtml .= $termsListView->getListItemHtml(
					$this->labelsProvider,
					$this->descriptionsProvider,
					$this->aliasesProvider,
					$languageCode
				);
			}
		}

		return $termsListView->getListViewHtml( $contentHtml );
	}

}
