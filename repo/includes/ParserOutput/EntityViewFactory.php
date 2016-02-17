<?php

namespace Wikibase\Repo\ParserOutput;
use OutOfBoundsException;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\EntityRevision;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityView;
use Wikibase\View\ViewFactory;
use Wikimedia\Assert\Assert;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityViewFactory {

	/**
	 * @var callable[]
	 */
	private $entityViewFactoryCallbacks;

	/**
	 * @param callable[] $entityViewFactoryCallbacks
	 */
	public function __construct( array $entityViewFactoryCallbacks ) {
		Assert::parameterElementType( 'callable', $entityViewFactoryCallbacks, '$entityViewFactoryCallbacks' );

		$this->entityViewFactoryCallbacks = $entityViewFactoryCallbacks;
	}

	/**
	 * @param string $entityType
	 * @param ViewFactory $viewFactory
	 * @param string $languageCode
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @throws OutOfBoundsException
	 * @return EntityView
	 */
	public function getEntityView(
		$entityType,
		ViewFactory $viewFactory,
		$languageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $languageFallbackChain,
		EditSectionGenerator $editSectionGenerator
	) {
		if ( !isset( $this->entityViewFactoryCallbacks[$entityType] ) ) {
			throw new OutOfBoundsException( "No EntityView is registered for entity type '$entityType'" );
		}

		$entityView = call_user_func(
			$this->entityViewFactoryCallbacks[$entityType],
			$viewFactory,
			$languageCode,
			$labelDescriptionLookup,
			$languageFallbackChain,
			$editSectionGenerator
		);

		Assert::postcondition(
			$entityView instanceof EntityView,
			'Callback must return an instance of EntityView'
		);

		return $entityView;
	}

}
