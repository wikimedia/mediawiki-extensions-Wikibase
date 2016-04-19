<?php

namespace Wikibase\Repo\Hooks;

use OutputPage;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\Repo\BabelUserLanguageLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\EntityViewPlaceholderExpander;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TextInjector;

/**
 * Handler for the "OutputPageBeforeHTML" hook.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class OutputPageBeforeHTMLHookHandler {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var UserLanguageLookup
	 */
	private $userLanguageLookup;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var OutputPageEntityIdReader
	 */
	private $outputPageEntityIdReader;

	/**
	 * @var EntityContentFactory
	 */
	private $entityContentFactory;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param UserLanguageLookup $userLanguageLookup
	 * @param ContentLanguages $termsLanguages
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param LanguageNameLookup $languageNameLookup
	 * @param OutputPageEntityIdReader $outputPageEntityIdReader
	 * @param EntityContentFactory $entityContentFactory
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		UserLanguageLookup $userLanguageLookup,
		ContentLanguages $termsLanguages,
		EntityRevisionLookup $entityRevisionLookup,
		LanguageNameLookup $languageNameLookup,
		OutputPageEntityIdReader $outputPageEntityIdReader,
		EntityContentFactory $entityContentFactory
	) {
		$this->templateFactory = $templateFactory;
		$this->userLanguageLookup = $userLanguageLookup;
		$this->termsLanguages = $termsLanguages;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->languageNameLookup = $languageNameLookup;
		$this->outputPageEntityIdReader = $outputPageEntityIdReader;
		$this->entityContentFactory = $entityContentFactory;
	}

	/**
	 * @return self
	 */
	public static function newFromGlobalState() {
		global $wgLang;

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$entityContentFactory = $wikibaseRepo->getEntityContentFactory();

		return new self(
			TemplateFactory::getDefaultInstance(),
			new BabelUserLanguageLookup,
			$wikibaseRepo->getTermsLanguages(),
			$wikibaseRepo->getEntityRevisionLookup(),
			new LanguageNameLookup( $wgLang->getCode() ),
			new OutputPageEntityIdReader(
				$entityContentFactory,
				$wikibaseRepo->getEntityIdParser()
			),
			$entityContentFactory
		);
	}

	/**
	 * Called when pushing HTML from the ParserOutput into OutputPage.
	 * Used to expand any placeholders in the OutputPage's 'wb-placeholders' property
	 * in the HTML.
	 *
	 * @param OutputPage $out
	 * @param string &$html the HTML to mangle
	 *
	 * @return bool
	 */
	public static function onOutputPageBeforeHTML( OutputPage $out, &$html ) {
		$self = self::newFromGlobalState();

		return $self->doOutputPageBeforeHTML( $out, $html );
	}

	/**
	 * @param OutputPage $out
	 * @param string &$html
	 *
	 * @return bool
	 */
	public function doOutputPageBeforeHTML( OutputPage $out, &$html ) {
		$placeholders = $out->getProperty( 'wikibase-view-chunks' );

		if ( !empty( $placeholders ) ) {
			// All user languages that are valid term languages
			$termsLanguages = array_intersect(
				$this->userLanguageLookup->getAllUserLanguages( $out->getUser() ),
				$this->termsLanguages->getLanguages()
			);

			$injector = new TextInjector( $placeholders );
			$expander = $this->getEntityViewPlaceholderExpander( $out, $termsLanguages );

			$html = $injector->inject( $html, array( $expander, 'getHtmlForPlaceholder' ) );

			$out->addJsConfigVars(
				'wbUserSpecifiedLanguages',
				// All user-specified languages, that are valid term languages
				// Reindex the keys so that javascript still works if an unknown
				// language code in the babel box causes an index to miss
				array_values( array_intersect(
					$this->userLanguageLookup->getUserSpecifiedLanguages( $out->getUser() ),
					$this->termsLanguages->getLanguages()
				) )
			);
		}
	}

	/**
	 * @param OutputPage $out
	 * @param string[] $termsLanguages
	 *
	 * @return EntityViewPlaceholderExpander
	 */
	private function getEntityViewPlaceholderExpander( OutputPage $out, array $termsLanguages ) {
		$languageCode = $out->getLanguage()->getCode();

		$entityId = $this->outputPageEntityIdReader->getEntityIdFromOutputPage( $out );
		$termsListItemsHtml = $out->getProperty( 'wikibase-terms-list-items' );

		if ( $termsListItemsHtml === null ) {
			// The parser cache content is too old to contain the terms list items
			// Pass the correct entity to generate terms list items on the fly
			$termsListItemsHtml = [];
			$revisionId = $out->getRevisionId();
			$entity = $this->entityRevisionLookup->getEntityRevision( $entityId, $revisionId )->getEntity();
		} else {
			$entity = $this->entityContentFactory->getContentHandlerForType( $entityId->getEntityType() )->makeEmptyEntity();
		}
		$labelsProvider = $entity;
		$descriptionsProvider = $entity;
		$aliasesProvider = $entity instanceof AliasesProvider ? $entity : null;


		return new EntityViewPlaceholderExpander(
			$this->templateFactory,
			$out->getUser(),
			$labelsProvider,
			$descriptionsProvider,
			$aliasesProvider,
			array_unique( array_merge( [ $languageCode ], $termsLanguages ) ),
			$this->languageNameLookup,
			new MediaWikiLocalizedTextProvider( $languageCode ),
			$termsListItemsHtml
		);
	}

}
