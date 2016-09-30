<?php

namespace Wikibase\Client\Hooks;

use EditPage;
use Html;
use IContextSource;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
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
	 * @param EditPage $editor
	 */
	public function handle( EditPage $editor ) {
		// Check if there is usage to show
		$title = $editor->getTitle();
		$usage = $this->usageLookup->getUsagesForPage( $title->getArticleID() );

		if ( $usage ) {
			$header = $this->getHeader();
			$usageOutput = $this->formatEntityUsage( $usage );
			$output = Html::rawElement(
				'div', [ 'class' => 'wikibase-entity-usage' ],
				$header . "\n" . $usageOutput
			);
			$editor->editFormTextAfterTools .= $output;
		}
	}

	/**
	 * @param string[] $rowAspects
	 * @param IContextSource $context
	 *
	 * @return string HTML
	 */
	private function formatAspects( array $rowAspects, IContextSource $context ) {
		$aspects = [];

		foreach ( $rowAspects as $aspect ) {
			$aspects[] = $context->msg(
				'wikibase-pageinfo-entity-usage-' . $aspect[0], $aspect[1] )->parse();
		}

		return $context->getLanguage()->commaList( $aspects );
	}

	/**
	 * @param EntityUsage[] $usage
	 * @return string HTML
	 */
	private function formatEntityUsage( array $usage ) {
		$usageAspectsByEntity = [];
		$entityIds = [];
		foreach ( $usage as $key => $entityUsage ) {
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
		$entityNames = array_map(
			function( $entityId ) {
				return $this->idParser->parse( $entityId );
			},
			array_keys( $usageAspectsByEntity )
		);
		$labelLookup = $this->labelDescriptionLookupFactory->newLabelDescriptionLookup(
			$this->context->getLanguage(),
			$entityNames
		);
		foreach ( $usageAspectsByEntity as $entityId => $aspects ) {
			$label = $labelLookup->getLabel( $entityIds[$entityId] );
			$text = $label === null ? $entityId : $label->getText();

			$aspectContent = $this->formatAspects( $aspects, $this->context );

			$output .= Html::rawElement( 'li', [],
				$this->repoLinker->buildEntityLink(
					$entityIds[$entityId],
					[ 'external' ],
					$text
				) . ': ' . $aspectContent
			);
		}
		$output = Html::rawElement( 'ul', [], $output );
		return $output;
	}

	/**
	 * @return string HTML
	 */
	private function getHeader() {
		$element = Html::rawElement(
			'div', [ 'class' => 'wikibase-entityusage-explanation' ],
			$this->context->msg( 'wikibase-pageinfo-entity-usage' )->parseAsBlock()
		);
		return $element;
	}

}
