<?php

namespace Wikibase\Repo\Hooks;

use IBufferingStatsdDataFactory;
use Language;
use MediaWiki\Hook\OutputPageBeforeHTMLHook;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\User\UserOptionsLookup;
use OutputPage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\BabelUserLanguageLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Hooks\Helpers\OutputPageEditability;
use Wikibase\Repo\Hooks\Helpers\OutputPageEntityViewChecker;
use Wikibase\Repo\Hooks\Helpers\OutputPageRevisionIdReader;
use Wikibase\Repo\Hooks\Helpers\UserPreferredContentLanguagesLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\EntityViewPlaceholderExpander;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\ExternallyRenderedEntityViewPlaceholderExpander;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\PlaceholderExpander;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\TermboxRequestInspector;
use Wikibase\Repo\ParserOutput\TermboxFlag;
use Wikibase\Repo\ParserOutput\TextInjector;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Termbox\Renderer\TermboxRemoteRenderer;
use Wikibase\View\ToolbarEditSectionGenerator;

/**
 * Handler for the "OutputPageBeforeHTML" hook.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class OutputPageBeforeHTMLHookHandler implements OutputPageBeforeHTMLHook {

	/** @var HttpRequestFactory */
	private $httpRequestFactory;

	/** @var IBufferingStatsdDataFactory */
	private $statsDataFactory;

	/** @var SettingsArray */
	private $repoSettings;

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

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
	 * @var OutputPageEditability
	 */
	private $editability;

	/**
	 * @var bool
	 */
	private $isExternallyRendered;

	/**
	 * @var UserPreferredContentLanguagesLookup
	 */
	private $userPreferredTermsLanguages;

	/**
	 * @var OutputPageEntityViewChecker
	 */
	private $entityViewChecker;

	/** @var LanguageFallbackChainFactory */
	private $languageFallbackChainFactory;

	/** @var LanguageDirectionalityLookup */
	private $languageDirectionalityLookup;

	/** @var UserOptionsLookup */
	private $userOptionsLookup;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		HttpRequestFactory $httpRequestFactory,
		IBufferingStatsdDataFactory $statsdDataFactory,
		SettingsArray $repoSettings,
		TemplateFactory $templateFactory,
		EntityRevisionLookup $entityRevisionLookup,
		LanguageNameLookup $languageNameLookup,
		OutputPageEntityIdReader $outputPageEntityIdReader,
		EntityFactory $entityFactory,
		$cookiePrefix,
		OutputPageEditability $editability,
		$isExternallyRendered,
		UserPreferredContentLanguagesLookup $userPreferredTermsLanguages,
		OutputPageEntityViewChecker $entityViewChecker,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		UserOptionsLookup $userOptionsLookup,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		LoggerInterface $logger = null
	) {
		$this->httpRequestFactory = $httpRequestFactory;
		$this->statsDataFactory = $statsdDataFactory;
		$this->repoSettings = $repoSettings;
		$this->templateFactory = $templateFactory;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->languageNameLookup = $languageNameLookup;
		$this->outputPageEntityIdReader = $outputPageEntityIdReader;
		$this->entityFactory = $entityFactory;
		$this->cookiePrefix = $cookiePrefix;
		$this->isExternallyRendered = $isExternallyRendered;
		$this->editability = $editability;
		$this->userPreferredTermsLanguages = $userPreferredTermsLanguages;
		$this->entityViewChecker = $entityViewChecker;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
		$this->logger = $logger ?: new NullLogger();
	}

	/**
	 * @return self
	 */
	public static function factory(
		Language $contentLanguage,
		HttpRequestFactory $httpRequestFactory,
		IBufferingStatsdDataFactory $statsdDataFactory,
		UserOptionsLookup $userOptionsLookup,
		EntityContentFactory $entityContentFactory,
		EntityFactory $entityFactory,
		EntityIdParser $entityIdParser,
		EntityRevisionLookup $entityRevisionLookup,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		LanguageNameLookup $languageNameLookup,
		LoggerInterface $logger,
		SettingsArray $settings,
		ContentLanguages $termsLanguages
	): self {
		global $wgCookiePrefix;

		$entityViewChecker = new OutputPageEntityViewChecker( $entityContentFactory );

		return new self(
			$httpRequestFactory,
			$statsdDataFactory,
			$settings,
			TemplateFactory::getDefaultInstance(),
			$entityRevisionLookup,
			$languageNameLookup,
			new OutputPageEntityIdReader(
				$entityViewChecker,
				$entityIdParser
			),
			$entityFactory,
			$wgCookiePrefix,
			new OutputPageEditability(),
			TermboxFlag::getInstance()->shouldRenderTermbox(),
			new UserPreferredContentLanguagesLookup(
				$termsLanguages,
				new BabelUserLanguageLookup(),
				$contentLanguage->getCode()
			),
			$entityViewChecker,
			$languageFallbackChainFactory,
			$userOptionsLookup,
			$languageDirectionalityLookup,
			$logger
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
	public function onOutputPageBeforeHTML( $out, &$html ): void {
		if ( !$this->entityViewChecker->hasEntityView( $out ) ) {
			return;
		}

		$html = $this->replacePlaceholders( $out, $html );
		$html = $this->showOrHideEditLinks( $out, $html );
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
		$getHtmlCallback = static function () {
			return '';
		};

		$entity = $this->getEntity( $out );
		if ( $entity instanceof EntityDocument ) {
			$getHtmlCallback = [ $this->getPlaceholderExpander( $entity, $out ), 'getHtmlForPlaceholder' ];
		}

		return $injector->inject( $html, $getHtmlCallback );
	}

	/**
	 * @param OutputPage $out
	 *
	 * @return EntityDocument|null
	 */
	private function getEntity( OutputPage $out ) {
		$entityId = $this->getEntityId( $out );

		if ( !$entityId ) {
			return null;
		}

		if ( $this->needsRealEntity( $out ) ) {
			// The parser cache content is too old to contain the terms list items
			// Pass the correct entity to generate terms list items on the fly
			$entityRev = $this->entityRevisionLookup->getEntityRevision( $entityId, $out->getRevisionId() );

			// T340642: Is this still needed? Log cases where a real entity was (attempted to be) loaded.
			if ( $entityRev instanceof EntityRevision ) {
				$logMessage = 'A real entity was loaded for entity id {entityId}.';
			} else {
				$logMessage = 'Loading a real entity for entity id {entityId} failed.';
			}

			$this->logger->warning(
				__METHOD__ . ': (T340642) ' . $logMessage,
				[
					'entityId' => $entityId->getSerialization(),
				]
			);

			if ( !( $entityRev instanceof EntityRevision ) ) {
				return null;
			}

			return $entityRev->getEntity();
		}

		return $this->entityFactory->newEmpty( $entityId->getEntityType() );
	}

	private function needsRealEntity( OutputPage $out ) {
		return !$this->isExternallyRendered && $this->getEntityTermsListHtml( $out ) === null;
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
		$user = $out->getUser();
		$entityTermsListHtml = $this->getEntityTermsListHtml( $out ) ?: [];

		return new EntityViewPlaceholderExpander(
			$this->templateFactory,
			$user,
			$entity,
			$this->getTermsLanguages(
				$this->userPreferredTermsLanguages->getLanguages( $language->getCode(), $user ),
				$entity,
				$entityTermsListHtml
			),
			$this->languageDirectionalityLookup,
			$this->languageNameLookup,
			new MediaWikiLocalizedTextProvider( $language ),
			$this->userOptionsLookup,
			$this->cookiePrefix,
			$entityTermsListHtml
		);
	}

	/**
	 * Get the term languages to use for the current user and entity.
	 */
	private function getTermsLanguages(
		array $userPreferredTermsLanguages,
		EntityDocument $entity,
		array $entityTermsListHtml
	): array {
		// The user already has "mul" in their preferred languages, nothing to do
		if ( in_array( 'mul', $userPreferredTermsLanguages ) ) {
			return $userPreferredTermsLanguages;
		}

		// Check both the html snippets and the (possibly empty) entity for a "mul" term.
		$hasMulTerm = isset( $entityTermsListHtml['mul'] );
		if ( $entity instanceof LabelsProvider ) {
			$hasMulTerm = $hasMulTerm || $entity->getLabels()->hasTermForLanguage( 'mul' );
		}
		if ( $entity instanceof AliasesProvider ) {
			$hasMulTerm = $hasMulTerm || $entity->getAliasGroups()->hasGroupForLanguage( 'mul' );
		}

		if ( $hasMulTerm ) {
			// There is a "mul" term present, show as last entry in the term box.
			return array_merge( $userPreferredTermsLanguages, [ 'mul' ] );
		}
		return $userPreferredTermsLanguages;
	}

	private function getExternallyRenderedEntityViewPlaceholderExpander( OutputPage $out ) {
		return new ExternallyRenderedEntityViewPlaceholderExpander(
			$out,
			new TermboxRequestInspector( $this->languageFallbackChainFactory ),
			new TermboxRemoteRenderer(
				$this->httpRequestFactory,
				$this->repoSettings->getSetting( 'ssrServerUrl' ),
				$this->repoSettings->getSetting( 'ssrServerTimeout' ),
				$this->logger,
				$this->statsDataFactory
			),
			$this->outputPageEntityIdReader,
			new RepoSpecialPageLinker(),
			$this->languageFallbackChainFactory,
			new OutputPageRevisionIdReader(),
			$this->repoSettings->getSetting( 'termboxUserSpecificSsrEnabled' )
		);
	}

	private function getEntityTermsListHtml( OutputPage $out ): ?array {
		return $out->getProperty( 'wikibase-terms-list-items' );
	}

	private function showOrHideEditLinks( OutputPage $out, $html ) {
		return ToolbarEditSectionGenerator::enableSectionEditLinks(
			$html,
			$this->editability->validate( $out )
		);
	}

	/**
	 * @param OutputPage $out
	 *
	 * @return EntityId|null
	 */
	private function getEntityId( OutputPage $out ) {
		return $this->outputPageEntityIdReader->getEntityIdFromOutputPage( $out );
	}

}
