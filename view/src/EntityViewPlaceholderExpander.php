<?php

namespace Wikibase\View;

use InvalidArgumentException;
use Language;
use MWException;
use RuntimeException;
use Title;
use User;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\View\Template\TemplateFactory;

/**
 * Utility for expanding the placeholders left in the HTML by EntityView.
 *
 * This is used to inject any non-cacheable information into the HTML
 * that was cached as part of the ParserOutput.
 *
 * @note This class encapsulated knowledge about which placeholders are used by
 * EntityView, and with what meaning.
 *
 * @see EntityView
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityViewPlaceholderExpander {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var Title
	 */
	private $targetPage;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var Language
	 */
	private $uiLanguage;

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
	 * @var UserLanguageLookup
	 */
	private $userLanguageLookup;

	/**
	 * @var string[]|null
	 */
	private $extraLanguages = null;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param Title $targetPage the page for which this expander is supposed to handle expansion.
	 * @param User $user the current user
	 * @param Language $uiLanguage the user's current UI language (as per the present request)
	 * @param LabelsProvider $labelsProvider
	 * @param DescriptionsProvider $descriptionsProvider
	 * @param AliasesProvider|null $aliasesProvider
	 * @param UserLanguageLookup $userLanguageLookup
	 * @param ContentLanguages $termsLanguages
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		Title $targetPage,
		User $user,
		Language $uiLanguage,
		LabelsProvider $labelsProvider,
		DescriptionsProvider $descriptionsProvider,
		AliasesProvider $aliasesProvider = null,
		UserLanguageLookup $userLanguageLookup,
		ContentLanguages $termsLanguages,
		LanguageNameLookup $languageNameLookup
	) {
		$this->targetPage = $targetPage;
		$this->user = $user;
		$this->uiLanguage = $uiLanguage;
		$this->labelsProvider = $labelsProvider;
		$this->descriptionsProvider = $descriptionsProvider;
		$this->aliasesProvider = $aliasesProvider;
		$this->userLanguageLookup = $userLanguageLookup;
		$this->templateFactory = $templateFactory;
		$this->termsLanguages = $termsLanguages;
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * Returns a list of languages desired by the user in addition to the current interface language.
	 *
	 * @see UserLanguageLookup
	 *
	 * @return string[]
	 */
	public function getExtraUserLanguages() {
		if ( $this->extraLanguages === null ) {
			if ( $this->user->isAnon() ) {
				// no extra languages for anon user
				$this->extraLanguages = array();
			} else {
				// ignore current interface language
				$skip = array( $this->uiLanguage->getCode() );
				$langs = array_diff(
					$this->userLanguageLookup->getAllUserLanguages( $this->user ),
					$skip
				);
				// Make sure we only report actual term languages
				$this->extraLanguages = array_intersect( $langs, $this->termsLanguages->getLanguages() );
			}
		}

		return $this->extraLanguages;
	}

	/**
	 * Callback for expanding placeholders to HTML,
	 * for use as a callback passed to with TextInjector::inject().
	 *
	 * @note This delegates to expandPlaceholder, which encapsulates knowledge about
	 * the meaning of each placeholder name, as used by EntityView.
	 *
	 * @param string $name the name (or kind) of placeholder; determines how the expansion is done.
	 * @param mixed [$arg,...] Additional arguments associated with the placeholder.
	 *
	 * @return string HTML to be substituted for the placeholder in the output.
	 */
	public function getHtmlForPlaceholder( $name /*...*/ ) {
		$args = func_get_args();
		$name = array_shift( $args );

		try {
			$html = $this->expandPlaceholder( $name, $args );
			return $html;
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
	 * @param array $args
	 *
	 * @return string
	 */
	protected function expandPlaceholder( $name, array $args ) {
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
		if ( $this->user->isAnon() ) {
			return isset( $_COOKIE['wikibase-entitytermsview-showEntitytermslistview'] )
				&& $_COOKIE['wikibase-entitytermsview-showEntitytermslistview'] === 'false';
		} else {
			return !$this->user->getBoolOption( 'wikibase-entitytermsview-showEntitytermslistview' );
		}
	 }

	/**
	 * Generates HTML of the term box, to be injected into the entity page.
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function renderTermBox() {
		$languages = array_merge(
			array( $this->uiLanguage->getCode() ),
			$this->getExtraUserLanguages()
		);

		$entityTermsView = new EntityTermsView(
			$this->templateFactory,
			null,
			$this->languageNameLookup,
			$this->uiLanguage->getCode()
		);

		$html = $entityTermsView->getEntityTermsForLanguageListView(
			$this->labelsProvider,
			$this->descriptionsProvider,
			$this->aliasesProvider,
			$languages,
			$this->targetPage
		);

		return $html;
	}

}
