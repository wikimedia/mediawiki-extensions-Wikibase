<?php

namespace Wikibase\Client\Hooks;

use Html;
use IContextSource;
use Title;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\WikibaseClient;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InfoActionHookHandler {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var UsageLookup
	 */
	private $usageLookup;

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelDescriptionLookupFactory;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	public function __construct(
		NamespaceChecker $namespaceChecker,
		RepoLinker $repoLinker,
		SiteLinkLookup $siteLinkLookup,
		$siteId,
		UsageLookup $usageLookup,
		LanguageFallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		EntityIdParser $idParser
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->repoLinker = $repoLinker;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteId = $siteId;
		$this->usageLookup = $usageLookup;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->idParser = $idParser;
	}

	/**
	 * Adds the Entity ID of the corresponding Wikidata item in action=info
	 *
	 * @param IContextSource $context
	 * @param array $pageInfo
	 *
	 * @return bool
	 */
	public static function onInfoAction( IContextSource $context, array &$pageInfo ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$namespaceChecker = $wikibaseClient->getNamespaceChecker();
		$usageLookup = $wikibaseClient->getStore()->getUsageLookup();
		$labelDescriptionLookupFactory = new LanguageFallbackLabelDescriptionLookupFactory(
			$wikibaseClient->getLanguageFallbackChainFactory(),
			$wikibaseClient->getTermLookup(),
			$wikibaseClient->getTermBuffer()
		);
		$idParser = $wikibaseClient->getEntityIdParser();

		$self = new self(
			$namespaceChecker,
			$wikibaseClient->newRepoLinker(),
			$wikibaseClient->getStore()->getSiteLinkLookup(),
			$settings->getSetting( 'siteGlobalID' ),
			$usageLookup,
			$labelDescriptionLookupFactory,
			$idParser
		);

		$pageInfo = $self->handle( $context, $pageInfo );

		return true;
	}

	/**
	 * @param IContextSource $context
	 * @param array $pageInfo
	 *
	 * @return array
	 */
	public function handle( IContextSource $context, array $pageInfo ) {
		// Check if wikibase namespace is enabled
		$title = $context->getTitle();
		$usage = $this->usageLookup->getUsagesForPage( $title->getArticleID() );

		if ( $this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) && $title->exists() ) {
			$pageInfo['header-basic'][] = $this->getPageInfoRow( $context, $title );
		}

		if ( $usage ) {
			$pageInfo['header-properties'][] = $this->formatEntityUsage( $context, $usage );
		}

		return $pageInfo;
	}

	/**
	 * @param IContextSource $context
	 * @param Title $title
	 *
	 * @return string[]
	 */
	private function getPageInfoRow( IContextSource $context, Title $title ) {
		$entityId = $this->siteLinkLookup->getItemIdForLink(
			$this->siteId,
			$title->getPrefixedText()
		);

		$row = $entityId ? $this->getItemPageInfo( $context, $entityId )
			: $this->getUnconnectedItemPageInfo( $context );

		return $row;
	}

	/**
	 * Creating a Repo link with Item ID as anchor text
	 *
	 * @param IContextSource $context
	 * @param ItemId $itemId
	 *
	 * @return string[]
	 */
	private function getItemPageInfo( IContextSource $context, ItemId $itemId ) {
		$itemLink = $this->repoLinker->buildEntityLink(
			$itemId,
			[ 'external' ]
		);

		return [
			$context->msg( 'wikibase-pageinfo-entity-id' ),
			$itemLink
		];
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return string[]
	 */
	private function getUnconnectedItemPageInfo( IContextSource $context ) {
		return [
			$context->msg( 'wikibase-pageinfo-entity-id' ),
			$context->msg( 'wikibase-pageinfo-entity-id-none' )
		];
	}

	private function formatAspects( array $aspects, IContextSource $context ) {
		$aspectContent = '';
		foreach ( $aspects as $aspect ) {
			$aspectContent .= Html::rawElement(
				'li',
				[],
				// Possible messages:
				//   wikibase-pageinfo-entity-usage-S
				//   wikibase-pageinfo-entity-usage-L
				//   wikibase-pageinfo-entity-usage-T
				//   wikibase-pageinfo-entity-usage-X
				//   wikibase-pageinfo-entity-usage-O
				$context->msg(
					'wikibase-pageinfo-entity-usage-' . $aspect[0], $aspect[1] )->parse()
			);
		}
		return $aspectContent;
	}

	/**
	 * @param IContextSource $context
	 * @param array $usage
	 *
	 * @return string[]
	 */
	private function formatEntityUsage( IContextSource $context, array $usage ) {
		$usageAspectsByEntity = [];
		$entities = [];
		foreach ( $usage as $key => $entityUsage ) {
			$entityId = $entityUsage->getEntityId()->getSerialization();
			$entities[$entityId] = $entityUsage->getEntityId();
			if ( !isset( $usageAspectsByEntity[$entityId] ) ) {
				$usageAspectsByEntity[$entityId] = [];
			}
			$usageAspectsByEntity[$entityId][] = [
				$entityUsage->getAspect(),
				$entityUsage->getModifier()
			];
		}
		$output = '';
		$entityIds = array_map(
			function( $entityId ) {
				return $this->idParser->parse( $entityId );
			},
			array_keys( $usageAspectsByEntity )
		);
		$labelLookup = $this->labelDescriptionLookupFactory->newLabelDescriptionLookup(
			$context->getLanguage(),
			$entityIds
		);
		foreach ( $usageAspectsByEntity as $entityId => $aspects ) {
			$label = $labelLookup->getLabel( $this->idParser->parse( $entityId ) );
			$text = $label === null ? $entityId : $label->getText();

			$output .= Html::rawElement( 'li', [],
				$this->repoLinker->buildEntityLink(
					$entities[$entityId],
					[ 'external' ],
					$text
				)
			);

			$aspectContent = $this->formatAspects( $aspects, $context );
			$output .= Html::rawElement( 'ul', [], $aspectContent );
		}
		$output = Html::rawElement( 'ul', [], $output );
		return [ $context->msg( 'wikibase-pageinfo-entity-usage' ), $output ];
	}

}
