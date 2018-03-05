<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use MWException;
use RuntimeException;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
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
 * @license GPL-2.0-or-later
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
	 * @var EntityDocument
	 */
	private $entity;

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
	 * @var string
	 */
	private $cookiePrefix;

	/**
	 * @var string[]
	 */
	private $termsListItems;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param User $user the current user
	 * @param EntityDocument $entity
	 * @param string[] $termsLanguages
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param LanguageNameLookup $languageNameLookup
	 * @param LocalizedTextProvider $textProvider
	 * @param string $cookiePrefix
	 * @param string[] $termsListItems
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		User $user,
		EntityDocument $entity,
		array $termsLanguages,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		LanguageNameLookup $languageNameLookup,
		LocalizedTextProvider $textProvider,
		$cookiePrefix,
		array $termsListItems = []
	) {
		$this->user = $user;
		$this->entity = $entity;
		$this->templateFactory = $templateFactory;
		$this->termsLanguages = $termsLanguages;
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
		$this->languageNameLookup = $languageNameLookup;
		$this->textProvider = $textProvider;
		$this->cookiePrefix = $cookiePrefix;
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
	 *
	 * @return string HTML to be substituted for the placeholder in the output.
	 */
	public function getHtmlForPlaceholder( $name ) {
		try {
			return $this->expandPlaceholder( $name );
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
	 * @note This encodes knowledge about which placeholders are used by EntityView with what
	 *       intended meaning.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function expandPlaceholder( $name ) {
		switch ( $name ) {
			case 'termbox':
				return $this->renderTermBox();
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
			$cookieName = $this->cookiePrefix . $name;
			if ( isset( $_COOKIE[$cookieName] ) ) {
				return $_COOKIE[$cookieName] === 'false';
			}
			// B/C: cookie set before 2017-11-06 are not prefixed. B/C code to be removed after 2018-11-14
			// once all old cookies have expired.
			return isset( $_COOKIE[$name] ) && $_COOKIE[$name] === 'false';
		} else {
			return !$this->user->getOption( $name, true );
		}
	}

	/**
	 * Generates HTML of the term box, to be injected into the entity page.
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function renderTermBox() {
		$termsListView = new TermsListView(
			$this->templateFactory,
			$this->languageNameLookup,
			$this->textProvider,
			$this->languageDirectionalityLookup
		);

		$contentHtml = '';
		foreach ( $this->termsLanguages as $languageCode ) {
			if ( isset( $this->termsListItems[ $languageCode ] ) ) {
				$contentHtml .= $this->termsListItems[ $languageCode ];
			} else {
				$contentHtml .= $termsListView->getListItemHtml(
					$this->entity instanceof LabelsProvider ? $this->entity->getLabels() : new TermList(),
					$this->entity instanceof DescriptionsProvider ? $this->entity->getDescriptions() : new TermList(),
					$this->entity instanceof AliasesProvider ? $this->entity->getAliasGroups() : null,
					$languageCode
				);
			}
		}

		return $termsListView->getListViewHtml( $contentHtml );
	}

}
