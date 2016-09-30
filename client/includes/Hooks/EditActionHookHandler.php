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
		RepoLinker $repoLinker,
		UsageLookup $usageLookup,
		LanguageFallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		EntityIdParser $idParser
	) {
		$this->repoLinker = $repoLinker;
		$this->usageLookup = $usageLookup;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->idParser = $idParser;
	}

	/**
	 * @param EditPage $editor
	 */
	public function handle( EditPage &$editor ) {
		// Check if there is usage to show
		$title = $editor->getTitle();
		$usage = $this->usageLookup->getUsagesForPage( $title->getArticleID() );

		if ( $usage ) {
			$header = $this->getHeader( $editor );
			$usageOutput = $this->formatEntityUsage( $editor, $usage );
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
	private function formatAspects( $rowAspects, IContextSource $context ) {
		$aspects = [];

		foreach ( $rowAspects as $aspect ) {
			$aspects[] = $context->msg(
				'wikibase-pageinfo-entity-usage-' . $aspect[0], $aspect[1] )->parse();
		}

		return $context->getLanguage()->commaList( $aspects );
	}

	/**
	 * @param EditPage $editor
	 * @param EntityUsage[] $usage
	 */
	private function formatEntityUsage( EditPage $editor, array $usage ) {
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
			$editor->getContext()->getLanguage(),
			$entityIds
		);
		foreach ( $usageAspectsByEntity as $entityId => $aspects ) {
			$label = $labelLookup->getLabel( $this->idParser->parse( $entityId ) );
			$text = $label === null ? $entityId : $label->getText();

			$aspectContent = $this->formatAspects( $aspects, $editor->getContext() );

			$output .= Html::rawElement( 'li', [],
				$this->repoLinker->buildEntityLink(
					$entities[$entityId],
					[ 'external' ],
					$text
				) . ': ' . $aspectContent
			);
		}
		$output = Html::rawElement( 'ul', [ 'class' => 'mw-editfooter-list mw-collapsible'],
			$output );
		return $output;
	}

	/**
	 * @param EditPage $editor
	 * @return string
	 */
	private function getHeader( EditPage $editor ) {
		$element = Html::rawelement( 'div', [
			'class' => 'wikibase-entityusage-explanation ' .
			             'mw-editfooter-toggler'
		], $editor->getContext()->msg( 'wikibase-entityusage-explanation' )->parse()
		);
		return $element;
	}

}
