<?php

namespace Wikibase\Repo\Hooks;

use OutputPage;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\EntityFactory;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\Repo\BabelUserLanguageLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Hooks\Helpers\OutputPageEditability;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\EntityViewPlaceholderExpander;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\ExternallyRenderedEntityViewPlaceholderExpander;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\PlaceholderExpander;
use Wikibase\Repo\ParserOutput\TermboxFlag;
use Wikibase\Repo\ParserOutput\TextInjector;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\Repo\ParserOutput\TermboxView;
use Wikibase\View\ToolbarEditSectionGenerator;

/**
 * Handler for the "OutputPageBeforeHTML" hook.
 *
 * @license GPL-2.0-or-later
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
	 * @var EntityFactory
	 */
	private $entityFactory;

	/**
	 * @var string
	 */
	private $cookiePrefix;

	/**
	 * @var EntityContentFactory
	 */
	private $entityContentFactory;

	/**
	 * @var OutputPageEditability
	 */
	private $editability;

	/**
	 * @var bool
	 */
	private $isExternallyRendered;

	public function __construct(
		TemplateFactory $templateFactory,
		UserLanguageLookup $userLanguageLookup,
		ContentLanguages $termsLanguages,
		EntityRevisionLookup $entityRevisionLookup,
		LanguageNameLookup $languageNameLookup,
		OutputPageEntityIdReader $outputPageEntityIdReader,
		EntityFactory $entityFactory,
		$cookiePrefix,
		EntityContentFactory $entityContentFactory,
		OutputPageEditability $editability,
		$isExternallyRendered = false
	) {
		$this->templateFactory = $templateFactory;
		$this->userLanguageLookup = $userLanguageLookup;
		$this->termsLanguages = $termsLanguages;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->languageNameLookup = $languageNameLookup;
		$this->outputPageEntityIdReader = $outputPageEntityIdReader;
		$this->entityFactory = $entityFactory;
		$this->cookiePrefix = $cookiePrefix;
		$this->entityContentFactory = $entityContentFactory;
		$this->editability = $editability;
		$this->isExternallyRendered = $isExternallyRendered;
	}

	/**
	 * @return self
	 */
	public static function newFromGlobalState() {
		global $wgLang, $wgCookiePrefix;

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
			$wikibaseRepo->getEntityFactory(),
			$wgCookiePrefix,
			$entityContentFactory,
			new OutputPageEditability(),
			TermboxFlag::getInstance()->shouldRenderTermbox()
		);
	}

	/**
	 * Called when pushing HTML from the ParserOutput into OutputPage.
	 * Used to expand any placeholders in the OutputPage's 'wb-placeholders' property
	 * in the HTML.
	 *
	 * @param OutputPage $out
	 * @param string &$html the HTML to mangle
	 */
	public static function onOutputPageBeforeHTML( OutputPage $out, &$html ) {
		self::newFromGlobalState()->doOutputPageBeforeHTML( $out, $html );
	}

	/**
	 * @param OutputPage $out
	 * @param string &$html
	 */
	public function doOutputPageBeforeHTML( OutputPage $out, &$html ) {
		if ( !$this->isEntityPage( $out ) ) {
			return;
		}

		$html = $this->replacePlaceholders( $out, $html );
		$this->addJsUserLanguages( $out );
		$html = $this->showOrHideEditLinks( $out, $html );
	}

	private function isEntityPage( OutputPage $out ) {
		return $this->entityContentFactory->isEntityContentModel( $out->getTitle()->getContentModel() );
	}

	/**
	 * @param OutputPage $out
	 * @param string $html
	 *
	 * @return string
	 */
	private function replacePlaceholders( OutputPage $out, $html ) {
		$placeholders = $out->getProperty( 'wikibase-view-chunks' );
		if ( !$placeholders ) {
			return $html;
		}

		$injector = new TextInjector( $placeholders );
		$getHtmlCallback = function() {
			return '';
		};

		$entity = $this->getEntity( $out );
		if ( $entity instanceof EntityDocument ) {
			$getHtmlCallback = [ $this->getPlaceholderExpander( $entity, $out ), 'getHtmlForPlaceholder' ];
		}

		return $injector->inject( $html, $getHtmlCallback );
	}

	private function addJsUserLanguages( OutputPage $out ) {
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

	/**
	 * @param OutputPage $out
	 *
	 * @return EntityDocument|null
	 */
	private function getEntity( OutputPage $out ) {
		$entityId = $this->outputPageEntityIdReader->getEntityIdFromOutputPage( $out );

		if ( !$entityId ) {
			return null;
		}

		if ( $this->needsRealEntity( $out ) ) {
			// The parser cache content is too old to contain the terms list items
			// Pass the correct entity to generate terms list items on the fly
			$entityRev = $this->entityRevisionLookup->getEntityRevision( $entityId, $out->getRevisionId() );
			if ( !( $entityRev instanceof EntityRevision ) ) {
				return null;
			}

			return $entityRev->getEntity();
		}

		return $this->entityFactory->newEmpty( $entityId->getEntityType() );
	}

	private function needsRealEntity( OutputPage $out ) {
		return !$this->isExternallyRendered && !$this->getEntityTermsListHtml( $out );
	}

	/**
	 * @param OutputPage $out
	 *
	 * @return string[]
	 */
	private function getTermsLanguagesCodes( OutputPage $out ) {
		// All user languages that are valid term languages
		return array_intersect(
			$this->userLanguageLookup->getAllUserLanguages( $out->getUser() ),
			$this->termsLanguages->getLanguages()
		);
	}

	private function getPlaceholderExpander(
		EntityDocument $entity,
		OutputPage $out
	): PlaceholderExpander {
		return $this->isExternallyRendered
			? $this->getExternallyRenderedEntityViewPlaceholderExpander( $out )
			: $this->getLocallyRenderedEntityViewPlaceholderExpander(
				$entity,
				$out
			);
	}

	/**
	 * @param EntityDocument $entity
	 * @param OutputPage $out
	 *
	 * @return EntityViewPlaceholderExpander
	 */
	private function getLocallyRenderedEntityViewPlaceholderExpander(
		EntityDocument $entity,
		OutputPage $out
	) {
		$language = $out->getLanguage();

		return new EntityViewPlaceholderExpander(
			$this->templateFactory,
			$out->getUser(),
			$entity,
			array_unique( array_merge( [ $language->getCode() ], $this->getTermsLanguagesCodes( $out ) ) ),
			new MediaWikiLanguageDirectionalityLookup(),
			$this->languageNameLookup,
			new MediaWikiLocalizedTextProvider( $language ),
			$this->cookiePrefix,
			$this->getEntityTermsListHtml( $out ) ?: []
		);
	}

	private function getExternallyRenderedEntityViewPlaceholderExpander( OutputPage $out ) {
		return new ExternallyRenderedEntityViewPlaceholderExpander(
			$out->getProperty( TermboxView::TERMBOX_MARKUP )
		);
	}

	private function getEntityTermsListHtml( OutputPage $out ) {
		return $out->getProperty( 'wikibase-terms-list-items' );
	}

	private function showOrHideEditLinks( OutputPage $out, $html ) {
		return ToolbarEditSectionGenerator::enableSectionEditLinks(
			$html,
			$this->editability->validate( $out )
		);
	}

}
