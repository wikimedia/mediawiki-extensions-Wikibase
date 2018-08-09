<?php


namespace Wikibase\Repo\ParserOutput;

use OutOfBoundsException;
use Wikibase\LanguageFallbackChain;
use Wikibase\View\EntityMetaTags;
use Wikimedia\Assert\Assert;

/**
 * A factory to create EntityMetaTags implementations by entity type based on callbacks.
 *
 * @license GNU GPL v2+
 */
class DispatchingEntityMetaTagsFactory {

	/**
	 * @var callable[]
	 */
	private $entityMetaTagsFactoryCallbacks;

	/**
	 * @param callable[] $entityMetaTagsFactoryCallbacks
	 */
	public function __construct( array $entityMetaTagsFactoryCallbacks ) {
		Assert::parameterElementType( 'callable', $entityMetaTagsFactoryCallbacks, '$entityMetaTagsFactoryCallbacks' );

		$this->entityMetaTagsFactoryCallbacks = $entityMetaTagsFactoryCallbacks;
	}

	public function newEntityMetaTags(
		$entityType,
		LanguageFallbackChain $languageFallbackChain
	) {
		if ( !isset( $this->entityMetaTagsFactoryCallbacks[$entityType] ) ) {
			throw new OutOfBoundsException( "No EntityMetaTags are registered for entity type '$entityType'" );
		}

		$entityMetaTagsClass = call_user_func( $this->entityMetaTagsFactoryCallbacks[$entityType] );

		Assert::postcondition(
			(
				class_exists( $entityMetaTagsClass ) &&
				in_array( EntityMetaTags::class, class_implements( $entityMetaTagsClass ) )
			),
			'Callback must return a class that implements EntityMetaTags'
		);

		$entityMetaTags = new $entityMetaTagsClass( $languageFallbackChain );

		return $entityMetaTags;
	}

}
