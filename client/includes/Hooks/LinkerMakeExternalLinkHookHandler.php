<?php

declare( strict_types=1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Context\RequestContext;
use MediaWiki\Hook\LinkerMakeExternalLinkHook;
use MediaWiki\Language\Language;
use MediaWiki\Title\Title;
use Wikibase\Client\Hooks\Formatter\ClientEntityLinkFormatter;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;

/**
 * @license GPL-2.0-or-later
 */
class LinkerMakeExternalLinkHookHandler implements LinkerMakeExternalLinkHook {
	private Language $contentLanguage;
	private EntityIdParser $entityIdParser;
	private bool $resolveWikibaseLabelsFlag;
	private string $repoUrlHost;
	private bool $isRepoEntityNamespaceMain;
	private ClientEntityLinkFormatter $clientEntityLinkFormatter;
	private FallbackLabelDescriptionLookup $labelDescriptionLookup;
	private ?Title $currentPageTitle;

	public function __construct(
		Language $contentLanguage,
		ClientEntityLinkFormatter $clientEntityLinkFormatter,
		EntityIdParser $entityIdParser,
		bool $isRepoEntityNamespaceMain,
		FallbackLabelDescriptionLookup $labelDescriptionLookup,
		string $repoUrlHost,
		bool $resolveWikibaseLabelsFlag,
		?Title $title
	) {
		$this->contentLanguage = $contentLanguage;
		$this->entityIdParser = $entityIdParser;
		$this->isRepoEntityNamespaceMain = $isRepoEntityNamespaceMain;
		$this->clientEntityLinkFormatter = $clientEntityLinkFormatter;
		$this->resolveWikibaseLabelsFlag = $resolveWikibaseLabelsFlag;
		$this->repoUrlHost = $repoUrlHost;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->currentPageTitle = $title;
	}

	public static function factory(
		Language $contentLanguage,
		ClientEntityLinkFormatter $clientEntityLinkFormatter,
		EntityIdParser $entityIdParser,
		EntityNamespaceLookup $entityNamespaceLookup,
		FallbackLabelDescriptionLookupFactory $fallbackLabelDescriptionLookupFactory,
		SettingsArray $settings
	): self {
		$context = RequestContext::getMain();
		$resolveWikibaseLabelsFlag = $settings->getSetting( 'resolveWikibaseLabels' );
		$repoUrlHost = parse_url( $settings->getSetting( 'repoUrl' ), PHP_URL_HOST );
		$language = $context->getUser()->isSafeToLoad() ? $context->getLanguage() : $contentLanguage;
		$terms = [ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION ];
		$labelDescriptionLookup = $fallbackLabelDescriptionLookupFactory->newLabelDescriptionLookup( $language, [], $terms );
		$isRepoEntityNamespaceMain = $entityNamespaceLookup->isEntityNamespace( 0 );
		return new self(
			$language,
			$clientEntityLinkFormatter,
			$entityIdParser,
			$isRepoEntityNamespaceMain,
			$labelDescriptionLookup,
			$repoUrlHost,
			$resolveWikibaseLabelsFlag,
			$context->getTitle()
		);
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	public function isRepoUrl( string $url ): bool {
		$parsedUrlHost = parse_url( $url, PHP_URL_HOST );

		return $parsedUrlHost === $this->repoUrlHost;
	}

	public function isRecentChangeOrWatchlist(): bool {
		return $this->currentPageTitle !== null && $this->currentPageTitle->isSpecialPage() &&
			( $this->currentPageTitle->isSpecial( 'Recentchanges' ) || $this->currentPageTitle->isSpecial( 'Watchlist' ) );
	}

	/**
	 * @param string &$url Link URL
	 * @param string &$text Link text
	 * @param string &$link New link HTML (if returning false)
	 * @param string[] &$attribs Attributes to be applied
	 * @param string $linkType External link type
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onLinkerMakeExternalLink( &$url, &$text, &$link, &$attribs, $linkType ) {
		if ( !$this->resolveWikibaseLabelsFlag || !$this->isRecentChangeOrWatchlist() || !$this->isRepoUrl( $url ) ) {
			return;
		}

		$explodedText = explode( ":", $text, 2 );

		// $explodedText may contain two elements: a namespace and entity ID (e.g. 'Property:P1' or 'Item:Q64'),
		// or just one element, item ID (e.g. 'Q64'), when the main namespace (0) of the linked repo is an entity namespace.
		// Therefore, the last element is always an entity Id,
		$entityId = trim( $explodedText[count( $explodedText ) - 1] );
		// and if there are multiple elements, the first is a namespace.
		$hasExplicitNamespace = count( $explodedText ) === 2;

		try {
			$parsedEntityId = $this->entityIdParser->parse( $entityId );
			if ( !( $parsedEntityId instanceof ItemId ) && !( $parsedEntityId instanceof NumericPropertyId ) ) {
				return;
			}
		} catch ( EntityIdParsingException ) {
			return;
		}

		// If there is no explicit namespace, the main namespace of the repo must be an entity namespace
		if ( !$hasExplicitNamespace && !$this->isRepoEntityNamespaceMain ) {
			return;
		}

		try {
			$labelData = $this->termFallbackToTermData( $this->labelDescriptionLookup->getLabel( $parsedEntityId ) );
			$descriptionData = $this->termFallbackToTermData( $this->labelDescriptionLookup->getDescription( $parsedEntityId ) );
		} catch ( LabelDescriptionLookupException ) {
			return;
		}

		if ( $labelData ) {
			$text = $this->clientEntityLinkFormatter->getHtml( $parsedEntityId, $this->contentLanguage, $labelData );
		}

		$titleAttribute = $this->clientEntityLinkFormatter->getTitleAttribute(
			$this->contentLanguage,
			$labelData,
			$descriptionData
		);
		if ( $titleAttribute ) {
			$attribs['title'] = $titleAttribute;
		}
	}

	/**
	 * @see HtmlPageLinkRendererEndHookHandler
	 *
	 * @param TermFallback|null $term
	 * @return string[]|null
	 */
	private function termFallbackToTermData( ?TermFallback $term ): ?array {
		if ( $term ) {
			return [
				'value' => $term->getText(),
				'language' => $term->getActualLanguageCode(),
			];
		}

		return null;
	}
}
