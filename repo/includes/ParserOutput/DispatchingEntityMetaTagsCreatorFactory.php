<?php

namespace Wikibase\Repo\ParserOutput;

use Language;
use OutOfBoundsException;
use Wikibase\View\EntityMetaTagsCreator;
use Wikimedia\Assert\Assert;

/**
 * A factory to create EntityMetaTags implementations by entity type based on callbacks.
 *
 * @license GNU GPL v2+
 */
class DispatchingEntityMetaTagsCreatorFactory {

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
		Language $userLanguage
	) {
		if ( !isset( $this->entityMetaTagsFactoryCallbacks[$entityType] ) ) {
			throw new OutOfBoundsException( "No EntityMetaTags are registered for entity type '$entityType'" );
		}

		$entityMetaTags = call_user_func( $this->entityMetaTagsFactoryCallbacks[$entityType], $userLanguage );

		Assert::postcondition(
			$entityMetaTags instanceof EntityMetaTagsCreator,
			'Callback must return an instance of EntityMetaTags'
		);

		return $entityMetaTags;
	}

}
