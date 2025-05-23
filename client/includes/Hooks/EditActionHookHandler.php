<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Context\IContextSource;
use MediaWiki\Hook\EditPage__showStandardInputs_optionsHook;
use MediaWiki\Html\Html;
use MessageLocalizer;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;

/**
 * Adds the Entity usage data in ActionEdit.
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani < ladsgroup@gmail.com >
 */
class EditActionHookHandler implements EditPage__showStandardInputs_optionsHook {

	private RepoLinker $repoLinker;

	private bool $isMobileView;

	private UsageLookup $usageLookup;

	private FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory;

	public function __construct(
		RepoLinker $repoLinker,
		bool $isMobileView,
		UsageLookup $usageLookup,
		FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory
	) {
		$this->repoLinker = $repoLinker;
		$this->isMobileView = $isMobileView;
		$this->usageLookup = $usageLookup;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
	}

	public static function factory(
		FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		bool $isMobileView,
		RepoLinker $repoLinker,
		ClientStore $store
	): self {
		$usageLookup = $store->getUsageLookup();

		return new self(
			$repoLinker,
			$isMobileView,
			$usageLookup,
			$labelDescriptionLookupFactory
		);
	}

	/**
	 * phpcs:ignore MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	 *
	 * @inheritDoc
	 */
	public function onEditPage__showStandardInputs_options( $editor, $out, &$tabindex ): void {
		if ( $editor->section ) {
			// Shorten out, like template transclusion in core
			return;
		}

		// Check if there are usages to show
		$title = $editor->getTitle();
		$usages = $this->usageLookup->getUsagesForPage( $title->getArticleID() );

		if ( $usages ) {
			$header = $this->getHeader( $out );
			$usageOutput = $this->formatEntityUsage( $usages, $out );
			$output = Html::rawElement(
				'div',
				[ 'class' => 'wikibase-entity-usage' ],
				$header . "\n" . $usageOutput
			);
			$editor->editFormTextAfterTools .= $output;
		}

		// T324991
		if ( !$this->isMobileView ) {
			$out->addModules( 'wikibase.client.action.edit.collapsibleFooter' );
		}
	}

	/**
	 * @param array[] $rowAspects
	 * @param IContextSource $context
	 *
	 * @return string HTML
	 */
	private function formatAspects( array $rowAspects, IContextSource $context ): string {
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
			$aspects[] = $context->msg( $msgKey, $aspect[1] )->parse();
		}

		return $context->getLanguage()->commaList( $aspects );
	}

	/**
	 * @param EntityUsage[] $usages
	 * @param IContextSource $context
	 * @return string HTML
	 */
	private function formatEntityUsage( array $usages, IContextSource $context ): string {
		$usageAspectsByEntity = [];
		$entityIds = [];

		foreach ( $usages as $entityUsage ) {
			$entityId = $entityUsage->getEntityId()->getSerialization();
			$entityIds[$entityId] = $entityUsage->getEntityId();
			$usageAspectsByEntity[$entityId][] = [
				$entityUsage->getAspect(),
				$entityUsage->getModifier(),
			];
		}

		$output = '';
		$labelLookup = $this->labelDescriptionLookupFactory->newLabelDescriptionLookup(
			$context->getLanguage(),
			array_values( $entityIds )
		);

		foreach ( $usageAspectsByEntity as $entityId => $aspects ) {
			$label = $labelLookup->getLabel( $entityIds[$entityId] );
			$text = $label === null ? $entityId : $label->getText();

			$aspectContent = $this->formatAspects( $aspects, $context );
			$colon = $context->msg( 'colon-separator' )->escaped();
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
	 * @param MessageLocalizer $context
	 * @return string HTML
	 */
	private function getHeader( MessageLocalizer $context ): string {
		return Html::rawElement(
			'div',
			[ 'class' => 'wikibase-entityusage-explanation' ],
			$context->msg( 'wikibase-pageinfo-entity-usage' )->parseAsBlock()
		);
	}

}
