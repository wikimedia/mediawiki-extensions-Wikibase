<?php
/**
 * A generator to create the text of titles of Entity pages by entity type based on callbacks.
 *
 * @license GNU GPL v2+
 */

namespace Wikibase\Repo\ParserOutput;

use OutOfBoundsException;
use Wikimedia\Assert\Assert;

class EntityPageTitleTextGenerator {

	private $entityPageTitleTextCallbacks;

	/**
	 * EntityPageTitleTextGenerator constructor.
	 *
	 * @param array $entityPageTitleTextCallbacks
	 */
	public function __construct(array $entityPageTitleTextCallbacks) {
		Assert::parameterElementType( 'callable', $entityPageTitleTextCallbacks, '$entityPageTitleTextCallbacks' );
		$this->entityPageTitleTextCallbacks = $entityPageTitleTextCallbacks;
	}

	public function generatePageTitleText($entity) {
		$entityType = $entity->getType();
		if ( !isset( $this->entityPageTitleTextCallbacks[$entityType] ) ) {
			throw new OutOfBoundsException( "No EntityView is registered for entity type '$entityType'" );
		}

		$entityPageTitleText = call_user_func(
			$this->entityViewFactoryCallbacks[$entityType],
			$entity
		);

		return $entityPageTitleText;
	}
}
