<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Specials;

use Html;
use HTMLForm;
use WebRequest;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Repo\Api\EntitySearchException;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\ItemDisambiguationFactory;

/**
 * Enables accessing items by providing the label of the item and the language of the label.
 * A result page is shown, disambiguating between multiple results if necessary.
 *
 * @license GPL-2.0-or-later
 */
class SpecialItemDisambiguation extends SpecialWikibasePage {

	private const LIMIT = 100;

	private EntitySearchHelper $entitySearchHelper;
	private ItemDisambiguationFactory $itemDisambiguationFactory;
	private LanguageNameLookupFactory $languageNameLookupFactory;
	private ContentLanguages $contentLanguages;

	public function __construct(
		EntitySearchHelper $entitySearchHelper,
		ItemDisambiguationFactory $itemDisambiguationFactory,
		LanguageNameLookupFactory $languageNameLookupFactory,
		ContentLanguages $contentLanguages
	) {
		parent::__construct( 'ItemDisambiguation' );

		$this->entitySearchHelper = $entitySearchHelper;
		$this->itemDisambiguationFactory = $itemDisambiguationFactory;
		$this->languageNameLookupFactory = $languageNameLookupFactory;
		$this->contentLanguages = $contentLanguages;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$request = $this->getRequest();
		$subPageParts = $subPage ? explode( '/', $subPage, 2 ) : [];

		$languageCode = $this->extractLanguageCode( $request, $subPageParts );
		$label = $this->extractLabel( $request, $subPageParts );

		$this->switchForm( $languageCode, $label );

		// Display the result set
		if ( $label !== '' ) {
			// @todo We could have a LanguageCodeValidator or something and handle this
			// in the search interactor or some place else.
			if ( !$this->contentLanguages->hasLanguage( $languageCode ) ) {
				$this->showErrorHTML(
					$this->msg( 'wikibase-itemdisambiguation-invalid-langcode' )->escaped()
				);
			} else {
				try {
					$searchResults = $this->getSearchResults( $label, $languageCode );

					if ( !empty( $searchResults ) ) {
						$this->displaySearchResults( $searchResults, $label );
					} else {
						$this->showNothingFound( $languageCode, $label );
					}
				} catch ( EntitySearchException $ese ) {
					$this->showErrorHTML( $ese->getStatus()->getHTML( 'search-error' ) );
				}
			}
		}
	}

	private function extractLanguageCode( WebRequest $request, array $subPageParts ): string {
		$languageCode = $request->getRawVal(
			'language',
			$subPageParts[0] ?? ''
		);

		if ( $languageCode === '' ) {
			$languageCode = $this->getLanguage()->getCode();
		}

		// @phan-suppress-next-line PhanTypeMismatchReturnNullable False positive getRawVal not return null here
		return $languageCode;
	}

	private function extractLabel( WebRequest $request, array $subPageParts ): string {
		if ( $request->getCheck( 'label' ) ) {
			return $request->getText( 'label' );
		}

		return isset( $subPageParts[1] ) ? str_replace( '_', ' ', $subPageParts[1] ) : '';
	}

	/**
	 * @param TermSearchResult[] $searchResults
	 * @param string $label
	 */
	private function displaySearchResults( array $searchResults, string $label ) {
		$this->getOutput()->setPageTitle(
			$this->msg( 'wikibase-disambiguation-title', $label )->escaped()
		);

		$this->displayDisambiguationPage( $searchResults );
	}

	/**
	 * @param string $label
	 * @param string $languageCode
	 *
	 * @return TermSearchResult[]
	 * @throws EntitySearchException
	 */
	private function getSearchResults( string $label, string $languageCode ): array {
		return $this->entitySearchHelper->getRankedSearchResults(
			$label,
			$languageCode,
			'item',
			self::LIMIT,
			false,
			null
		);
	}

	/**
	 * Shows information, assuming no results were found.
	 */
	private function showNothingFound( string $languageCode, string $label ): void {
		$this->getOutput()->addWikiMsg( 'wikibase-itemdisambiguation-nothing-found' );

		if ( $languageCode === $this->getLanguage()->getCode() ) {
			$searchLink = $this->getTitleFor( 'Search' );
			$this->getOutput()->addWikiMsg(
				'wikibase-itemdisambiguation-search',
				$searchLink->getFullURL( [ 'search' => $label ] )
			);
			$createLink = $this->getTitleFor( 'NewItem' );
			$this->getOutput()->addWikiMsg(
				'wikibase-itemdisambiguation-create',
				$createLink->getFullURL( [ 'label' => $label ] )
			);
		}
	}

	/**
	 * Display disambiguation page.
	 *
	 * @param TermSearchResult[] $searchResults
	 */
	private function displayDisambiguationPage( array $searchResults ): void {
		$itemDisambiguation = $this->itemDisambiguationFactory
			->getForLanguage( $this->getLanguage() );
		$html = $itemDisambiguation->getHTML( $searchResults );
		$this->getOutput()->addHTML( $html );
	}

	/**
	 * Return options for the language input field.
	 */
	private function getLanguageOptions(): array {
		$languageNameLookup = $this->languageNameLookupFactory
			->getForLanguage( $this->getLanguage() );
		$options = [];
		foreach ( $this->contentLanguages->getLanguages() as $languageCode ) {
			$languageName = $languageNameLookup->getName( $languageCode );
			$options["$languageName ($languageCode)"] = $languageCode;
		}
		return $options;
	}

	/**
	 * Output a form to allow searching for labels
	 */
	private function switchForm( ?string $languageCode, ?string $label ): void {
		$formDescriptor = [
			'language' => [
				'name' => 'language',
				'default' => $languageCode ?: '',
				'type' => 'combobox',
				'options' => $this->getLanguageOptions(),
				'id' => 'wb-itemdisambiguation-languagename',
				'size' => 12,
				'cssclass' => 'wb-language-suggester',
				'label-message' => 'wikibase-itemdisambiguation-lookup-language',
			],
			'label' => [
				'name' => 'label',
				'default' => $label ?: '',
				'type' => 'text',
				'id' => 'labelname',
				'size' => 36,
				'autofocus',
				'label-message' => 'wikibase-itemdisambiguation-lookup-label',
			],
			'submit' => [
				'name' => '',
				'default' => $this->msg( 'wikibase-itemdisambiguation-submit' )->text(),
				'type' => 'submit',
				'id' => 'wb-itembytitle-submit',
			],
		];

		HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setId( 'wb-itemdisambiguation-form1' )
			->setMethod( 'get' )
			->setFooterHtml( Html::element(
				'p',
				[],
				$this->msg( 'wikibase-itemdisambiguation-form-hints' )->numParams(
					self::LIMIT
				)->text()
			) )
			->setWrapperLegendMsg( 'wikibase-itemdisambiguation-lookup-fieldset' )
			->suppressDefaultSubmit()
			->setSubmitCallback( function () {// no-op
			} )->show();
	}

}
