<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityView;
use Wikibase\View\ViewFactory;

/**
 * A factory to create EntityView implementations based on entity type.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class DispatchingEntityViewFactory {

	/**
	 * @var ViewFactory
	 */
	private $viewFactory;

	/**
	 * @param ViewFactory $viewFactory
	 */
	public function __construct( ViewFactory $viewFactory ) {
		$this->viewFactory = $viewFactory;
	}

	/**
	 * Creates a new EntityView that can display the given type of entity.
	 *
	 * @param string $entityType
	 * @param string $languageCode
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @throws InvalidArgumentException
	 * @return EntityView
	 */
	public function newEntityView(
		$entityType,
		$languageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $languageFallbackChain,
		EditSectionGenerator $editSectionGenerator
	) {
		switch ( $entityType ) {
			case 'item':
				return $this->viewFactory->newItemView(
					$languageCode,
					$labelDescriptionLookup,
					$languageFallbackChain,
					$editSectionGenerator
				);
			case 'property':
				return $this->viewFactory->newPropertyView(
					$languageCode,
					$labelDescriptionLookup,
					$languageFallbackChain,
					$editSectionGenerator
				);
			default:
				throw new InvalidArgumentException( 'No EntityView for entity type: ' . $entityType );
		}
	}

}
