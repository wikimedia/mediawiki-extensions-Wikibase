<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use IBufferingStatsdDataFactory;
use Language;
use MediaWiki\Hook\OutputPageBeforeHTMLHook;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\User\Options\UserOptionsLookup;
use OutputPage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\SettingsArray;
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

	private HttpRequestFactory $httpRequestFactory;
	private IBufferingStatsdDataFactory $statsDataFactory;
	private SettingsArray $repoSettings;
	private TemplateFactory $templateFactory;
	private EntityRevisionLookup $entityRevisionLookup;
	private LanguageNameLookupFactory $languageNameLookupFactory;
	private OutputPageEntityIdReader $outputPageEntityIdReader;
	private EntityFactory $entityFactory;
	private string $cookiePrefix;
	private OutputPageEditability $editability;
	private bool $isExternallyRendered;
	private UserPreferredContentLanguagesLookup $userPreferredTermsLanguages;
	private OutputPageEntityViewChecker $entityViewChecker;
	private LanguageFallbackChainFactory $languageFallbackChainFactory;
	private LanguageDirectionalityLookup $languageDirectionalityLookup;
	private UserOptionsLookup $userOptionsLookup;
	private LoggerInterface $logger;

	public function __construct(
		HttpRequestFactory $httpRequestFactory,
		IBufferingStatsdDataFactory $statsdDataFactory,
		SettingsArray $repoSettings,
		TemplateFactory $templateFactory,
		EntityRevisionLookup $entityRevisionLookup,
		LanguageNameLookupFactory $languageNameLookupFactory,
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
		$this->languageNameLookupFactory = $languageNameLookupFactory;
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
		LanguageNameLookupFactory $languageNameLookupFactory,
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
			$languageNameLookupFactory,
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

	private function replacePlaceholders( OutputPage $out, string $html ): string {
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

	private function getEntity( OutputPage $out ): ?EntityDocument {
		$entityId = $this->getEntityId( $out );

		if ( !$entityId ) {
			return null;
		}

		// Previously, this would sometimes load the entity from the EntityRevisionLookup.
		// However, this is currently not needed: the parser cache content has all the term list items,
		// so we can just use a blank entity to render the remaining "no terms" rows,
		// optionally hydrated with labels, also from the parser cache, in order to render label placeholder fallbacks.

		$entity = $this->entityFactory->newEmpty( $entityId->getEntityType() );

		if ( $entity instanceof LabelsProvider ) {
			$labelsTermList = $entity->getLabels();
			$entityLabels = $out->getProperty( 'wikibase-entity-labels' ) ?: [];
			if ( $labelsTermList->isEmpty() && count( $entityLabels ) > 0 ) {
				foreach ( $entityLabels as $languageCode => $label ) {
					$labelsTermList->setTextForLanguage( $languageCode, $label );
				}
			}
		}

		return $entity;
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

	private function getLocallyRenderedEntityViewPlaceholderExpander(
		EntityDocument $entity,
		OutputPage $out
	): EntityViewPlaceholderExpander {
		$language = $out->getLanguage();
		$user = $out->getUser();
		$entityTermsListHtml = $this->getEntityTermsListHtml( $out );

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
			$this->languageNameLookupFactory->getForLanguage( $language ),
			new MediaWikiLocalizedTextProvider( $language ),
			$this->userOptionsLookup,
			$this->cookiePrefix,
			$this->languageFallbackChainFactory,
			$this->repoSettings->getSetting( 'tmpEnableMulLanguageCode' ),
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

		if (
			$this->repoSettings->getSetting( 'tmpEnableMulLanguageCode' )
			&& $this->repoSettings->getSetting( 'tmpAlwaysShowMulLanguageCode' )
		) {
			return array_merge( $userPreferredTermsLanguages, [ 'mul' ] );
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

	private function getExternallyRenderedEntityViewPlaceholderExpander(
		OutputPage $out
	): ExternallyRenderedEntityViewPlaceholderExpander {
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

	private function getEntityTermsListHtml( OutputPage $out ): array {
		$items = $out->getProperty( 'wikibase-terms-list-items' );
		if ( is_array( $items ) ) {
			return $items;
		} elseif ( $items === null ) {
			throw new RuntimeException(
				'OutputPage missing wikibase-terms-list-items: ' . $out->getTitle()->getPrefixedText()
			);
		} else {
			throw new RuntimeException(
				'Unexpected type ' . gettype( $items ) . ' for wikibase-terms-list-items: ' . $out->getTitle()->getPrefixedText()
			);
		}
	}

	private function showOrHideEditLinks( OutputPage $out, string $html ): string {
		return ToolbarEditSectionGenerator::enableSectionEditLinks(
			$html,
			$this->editability->validate( $out )
		);
	}

	private function getEntityId( OutputPage $out ): ?EntityId {
		return $this->outputPageEntityIdReader->getEntityIdFromOutputPage( $out );
	}

}
