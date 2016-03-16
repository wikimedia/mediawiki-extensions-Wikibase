<?php

namespace Wikibase\Repo\Hooks;

use OutputPage;
use Revision;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\Repo\BabelUserLanguageLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\EntityIdLookup;
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
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param UserLanguageLookup $userLanguageLookup
	 * @param ContentLanguages $termsLanguages
	 * @param EntityIdParser $entityIdParser
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		UserLanguageLookup $userLanguageLookup,
		ContentLanguages $termsLanguages,
		EntityIdParser $entityIdParser,
		EntityRevisionLookup $entityRevisionLookup,
		LanguageNameLookup $languageNameLookup,
		EntityIdLookup $entityIdLookup
	) {
		$this->templateFactory = $templateFactory;
		$this->userLanguageLookup = $userLanguageLookup;
		$this->termsLanguages = $termsLanguages;
		$this->entityIdParser = $entityIdParser;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->languageNameLookup = $languageNameLookup;
		$this->entityIdLookup = $entityIdLookup;
	}

	/**
	 * @return self
	 */
	public static function newFromGlobalState() {
		global $wgLang;

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$entityIdParser = $wikibaseRepo->getEntityIdParser();

		return new self(
			TemplateFactory::getDefaultInstance(),
			new BabelUserLanguageLookup,
			$wikibaseRepo->getTermsLanguages(),
			$entityIdParser,
			$wikibaseRepo->getEntityRevisionLookup(),
			new LanguageNameLookup( $wgLang->getCode() ),
			$wikibaseRepo->getEntityIdLookup()
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
			$injector = new TextInjector( $placeholders );
			$expander = $this->getEntityViewPlaceholderExpander( $out );

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
	 *
	 * @return EntityViewPlaceholderExpander
	 */
	private function getEntityViewPlaceholderExpander( OutputPage $out ) {

		$entityId = $this->entityIdLookup->getEntityIdForTitle( $out->getTitle() );
		$revisionId = $out->getRevisionId();
		$entity = $this->entityRevisionLookup->getEntityRevision( $entityId, $revisionId )->getEntity();
		$labelsProvider = $entity;
		$descriptionsProvider = $entity;
		$aliasesProvider = $entity;

		return new EntityViewPlaceholderExpander(
			$this->templateFactory,
			$out->getTitle(),
			$out->getUser(),
			$out->getLanguage(),
			$this->entityIdParser,
			$labelsProvider,
			$descriptionsProvider,
			$aliasesProvider,
			$this->userLanguageLookup,
			$this->termsLanguages,
			$this->languageNameLookup
		);
	}

}
