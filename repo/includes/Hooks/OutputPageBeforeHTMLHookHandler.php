<?php

namespace Wikibase\Repo\Hooks;

use Language;
use OutputPage;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityFactory;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\Repo\BabelUserLanguageLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\ParserOutput\EntityViewPlaceholderExpander;
use Wikibase\Repo\ParserOutput\TextInjector;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\Template\TemplateFactory;
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

	public function __construct(
		TemplateFactory $templateFactory,
		UserLanguageLookup $userLanguageLookup,
		ContentLanguages $termsLanguages,
		EntityRevisionLookup $entityRevisionLookup,
		LanguageNameLookup $languageNameLookup,
		OutputPageEntityIdReader $outputPageEntityIdReader,
		EntityFactory $entityFactory,
		$cookiePrefix,
		EntityContentFactory $entityContentFactory
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

		$this->replacePlaceholders( $out, $html );
		$this->addJsUserLanguages( $out );
		$html = $this->showOrHideEditLinks( $out, $html );
	}

	private function isEntityPage( OutputPage $out ) {
		return $this->entityContentFactory->isEntityContentModel( $out->getTitle()->getContentModel() );
	}

	/**
	 * @param OutputPage $out
	 * @param string &$html
	 */
	private function replacePlaceholders( OutputPage $out, &$html ) {
		$placeholders = $out->getProperty( 'wikibase-view-chunks' );
		if ( !$placeholders ) {
			return;
		}
		$injector = new TextInjector( $placeholders );
		$getHtmlCallback = function() {
			return '';
		};

		$entityId = $this->outputPageEntityIdReader->getEntityIdFromOutputPage( $out );
		if ( $entityId instanceof EntityId ) {
			$termsListItemsHtml = $out->getProperty( 'wikibase-terms-list-items' );
			$entity = $this->getEntity( $entityId, $out->getRevisionId(), $termsListItemsHtml !== null );
			if ( $entity instanceof EntityDocument ) {
				$expander = $this->getEntityViewPlaceholderExpander(
					$entity,
					$out->getUser(),
					$this->getTermsLanguagesCodes( $out ),
					$termsListItemsHtml,
					$out->getLanguage()
				);
				$getHtmlCallback = [ $expander, 'getHtmlForPlaceholder' ];
			}
		}

		$html = $injector->inject( $html, $getHtmlCallback );
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
	 * @param EntityId $entityId
	 * @param int $revisionId
	 * @param bool $termsListPrerendered
	 *
	 * @return EntityDocument|null
	 */
	private function getEntity( EntityId $entityId, $revisionId, $termsListPrerendered ) {
		if ( $termsListPrerendered ) {
			$entity = $this->entityFactory->newEmpty( $entityId->getEntityType() );
		} else {
			// The parser cache content is too old to contain the terms list items
			// Pass the correct entity to generate terms list items on the fly
			$entityRev = $this->entityRevisionLookup->getEntityRevision( $entityId, $revisionId );
			if ( !( $entityRev instanceof EntityRevision ) ) {
				return null;
			}
			$entity = $entityRev->getEntity();
		}
		return $entity;
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

	/**
	 * @param EntityDocument $entity
	 * @param User $user
	 * @param string[] $termsLanguages
	 * @param string[]|null $termsListItemsHtml
	 * @param Language $language
	 *
	 * @return EntityViewPlaceholderExpander
	 */
	private function getEntityViewPlaceholderExpander(
		EntityDocument $entity,
		User $user,
		array $termsLanguages,
		array $termsListItemsHtml = null,
		Language $language
	) {
		return new EntityViewPlaceholderExpander(
			$this->templateFactory,
			$user,
			$entity,
			array_unique( array_merge( [ $language->getCode() ], $termsLanguages ) ),
			new MediaWikiLanguageDirectionalityLookup(),
			$this->languageNameLookup,
			new MediaWikiLocalizedTextProvider( $language ),
			$this->cookiePrefix,
			$termsListItemsHtml ?: []
		);
	}

	private function showOrHideEditLinks( OutputPage $out, $html ) {
		return ToolbarEditSectionGenerator::enableSectionEditLinks(
			$html,
			$this->isEditable( $out )
		);
	}

	private function isEditable( OutputPage $out ) {
		return $this->isProbablyEditable( $out->getUser(), $out->getTitle() )
			&& $this->isEditView( $out );
	}

	/**
	 * This is duplicated from
	 * @see OutputPage::getJSVars - wgIsProbablyEditable
	 *
	 * @param User $user
	 * @param Title $title
	 *
	 * @return bool
	 */
	private function isProbablyEditable( User $user, Title $title ) {
		return $title->quickUserCan( 'edit', $user )
			&& ( $title->exists() || $title->quickUserCan( 'create', $user ) );
	}

	/**
	 * This is mostly a duplicate of
	 * @see \Wikibase\ViewEntityAction::isEditable
	 *
	 * @param OutputPage $out
	 *
	 * @return bool
	 */
	private function isEditView( OutputPage $out ) {
		return $this->isLatestRevision( $out )
			&& !$this->isDiff( $out )
			&& !$out->isPrintable();
	}

	private function isDiff( OutputPage $out ) {
		return $out->getRequest()->getCheck( 'diff' );
	}

	private function isLatestRevision( OutputPage $out ) {
		return !$out->getRevisionId() // the revision id can be null on a ParserCache hit, but only for the latest revision
			|| $out->getRevisionId() === $out->getTitle()->getLatestRevID();
	}

}
