<?php
declare( strict_types = 1 );
namespace Wikibase\Repo\Rdf;

use Wikimedia\Assert\Assert;
use Wikimedia\Purtle\RdfWriter;

/**
 * Factory for EntityStubRdfBuilderFactory based on factory callbacks.
 * For use with EntityTypeDefinitions.
 *
 * @license GPL-2.0-or-later
 */
class EntityStubRdfBuilderFactory {

	/**
	 * @var callable[]
	 */
	private $factoryCallbacks;

	/**
	 * @param callable[] $factoryCallbacks Factory callback functions as returned for
	 *        EntityTypeDefinitions::RDF_BUILDER_STUB_FACTORY_CALLBACK. Callbacks will be invoked
	 *        with the signature ( RdfVocabulary, RdfWrite ) and must return a EntityRdfBuilder
	 */
	public function __construct( array $factoryCallbacks ) {
		Assert::parameterElementType( 'callable', $factoryCallbacks, '$factoryCallbacks' );

		$this->factoryCallbacks = $factoryCallbacks;
	}

	/**
	 * Returns an associative array mapping entity type to EntityStubRdfBuilder implementations
	 *
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $writer
	 *
	 * @return EntityStubRdfBuilder[] array mapping entity types to their EntityStubRdfBuilder
	 */
	public function getEntityStubRdfBuilders(
		RdfVocabulary $vocabulary,
		RdfWriter $writer
	): array {
		return $this->createEntityRdfBuilders(
			$vocabulary,
			$writer
		);
	}

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $writer
	 *
	 * @return EntityStubRdfBuilder[] array mapping entity types to their EntityStubRdfBuilder
	 */
	private function createEntityRdfBuilders(
		RdfVocabulary $vocabulary,
		RdfWriter $writer
	): array {
		$builders = [];

		foreach ( $this->factoryCallbacks as $key => $callback ) {
			$builders[ $key ] = call_user_func(
				$callback,
				$vocabulary,
				$writer
			);

			Assert::postcondition(
				$builders[$key] instanceof EntityStubRdfBuilder,
				"builder for $key is not an EntityStubRdfBuilder"
			);
		}

		return $builders;
	}

}
