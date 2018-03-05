<?php

namespace Wikibase\Client\Hooks;

use EditPage;
use Html;
use IContextSource;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;

/**
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani < ladsgroup@gmail.com >
 */
class EditActionHookHandler {

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

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

	/**
	 * @var IContextSource
	 */
	private $context;

	public function __construct(
		RepoLinker $repoLinker,
		UsageLookup $usageLookup,
		LanguageFallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		EntityIdParser $idParser,
		IContextSource $context
	) {
		$this->repoLinker = $repoLinker;
		$this->usageLookup = $usageLookup;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->idParser = $idParser;
		$this->context = $context;
	}

	/**
	 * @param IContextSource $context
	 * @return self
	 */
	public static function newFromGlobalState( IContextSource $context ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$usageLookup = $wikibaseClient->getStore()->getUsageLookup();
		$labelDescriptionLookupFactory = new LanguageFallbackLabelDescriptionLookupFactory(
			$wikibaseClient->getLanguageFallbackChainFactory(),
			$wikibaseClient->getTermLookup(),
			$wikibaseClient->getTermBuffer()
		);
		$idParser = $wikibaseClient->getEntityIdParser();

		return new self(
			$wikibaseClient->newRepoLinker(),
			$usageLookup,
			$labelDescriptionLookupFactory,
			$idParser,
			$context
		);
	}

	public function handle( EditPage $editor ) {
		// Check if there are usages to show
		$title = $editor->getTitle();
		$usages = $this->usageLookup->getUsagesForPage( $title->getArticleID() );

		if ( $usages ) {
			$header = $this->getHeader();
			$usageOutput = $this->formatEntityUsage( $usages );
			$output = Html::rawElement(
				'div',
				[ 'class' => 'wikibase-entity-usage' ],
				$header . "\n" . $usageOutput
			);
			$editor->editFormTextAfterTools .= $output;
		}
	}

	/**
	 * @param string[] $rowAspects
	 *
	 * @return string HTML
	 */
	private function formatAspects( array $rowAspects ) {
		$aspects = [];

		foreach ( $rowAspects as $aspect ) {
			// Possible messages:
			//   wikibase-pageinfo-entity-usage-L
			//   wikibase-pageinfo-entity-usage-L-with-modifier
			//   wikibase-pageinfo-entity-usage-D
			//   wikibase-pageinfo-entity-usage-D-with-modifier
			//   wikibase-pageinfo-entity-usage-C
			//   wikibase-pageinfo-entity-usage-C-with-modifier
			//   wikibase-pageinfo-entity-usage-S
			//   wikibase-pageinfo-entity-usage-T
			//   wikibase-pageinfo-entity-usage-X
			//   wikibase-pageinfo-entity-usage-O
			$msgKey = 'wikibase-pageinfo-entity-usage-' . $aspect[0];
			if ( $aspect[1] !== null ) {
				$msgKey .= '-with-modifier';
			}
			$aspects[] = $this->context->msg( $msgKey, $aspect[1] )->parse();
		}

		return $this->context->getLanguage()->commaList( $aspects );
	}

	/**
	 * @param EntityUsage[] $usages
	 * @return string HTML
	 */
	private function formatEntityUsage( array $usages ) {
		$usageAspectsByEntity = [];
		$entityIds = [];

		foreach ( $usages as $key => $entityUsage ) {
			$entityId = $entityUsage->getEntityId()->getSerialization();
			$entityIds[$entityId] = $entityUsage->getEntityId();
			if ( !isset( $usageAspectsByEntity[$entityId] ) ) {
				$usageAspectsByEntity[$entityId] = [];
			}
			$usageAspectsByEntity[$entityId][] = [
				$entityUsage->getAspect(),
				$entityUsage->getModifier()
			];
		}

		$output = '';
		$labelLookup = $this->labelDescriptionLookupFactory->newLabelDescriptionLookup(
			$this->context->getLanguage(),
			array_values( $entityIds )
		);

		foreach ( $usageAspectsByEntity as $entityId => $aspects ) {
			$label = $labelLookup->getLabel( $entityIds[$entityId] );
			$text = $label === null ? $entityId : $label->getText();

			$aspectContent = $this->formatAspects( $aspects );
			$colon = $this->context->msg( 'colon-separator' )->plain();
			$output .= Html::rawElement(
				'li',
				[],
				$this->repoLinker->buildEntityLink(
					$entityIds[$entityId],
					[ 'external' ],
					$text
				) . $colon . $aspectContent
			);
		}
		return Html::rawElement( 'ul', [], $output );
	}

	/**
	 * @return string HTML
	 */
	private function getHeader() {
		return Html::rawElement(
			'div',
			[ 'class' => 'wikibase-entityusage-explanation' ],
			$this->context->msg( 'wikibase-pageinfo-entity-usage' )->parseAsBlock()
		);
	}

}
