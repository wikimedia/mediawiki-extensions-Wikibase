<?php

namespace Wikibase\Rdf;

use Wikimedia\Assert\Assert;
use Wikimedia\Purtle\RdfWriter;

/**
 * Factory for ValueSnakRdfBuilder based on factory callbacks.
 * For use with DataTypeDefinitions.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class EntityRdfBuilderFactory {

	/**
	 * @var callable[]
	 */
	private $factoryCallbacks;

	/**
	 * @param callable[] $factoryCallbacks Factory callback functions as returned by
	 *        EntityTypeDefinitions::getRdfBuilderFactoryCallbacks(). Callbacks will be invoked
	 *        with the signature ($mode, RdfVocabulary, EntityMentionListener) and must
	 *        return a EntityRdfBuilder (or null).
	 */
	public function __construct( array $factoryCallbacks ) {
		Assert::parameterElementType( 'callable', $factoryCallbacks, '$factoryCallbacks' );

		$this->factoryCallbacks = $factoryCallbacks;
	}

	/**
	 * Returns an EntityRdfBuilder for reified value output.
	 *
	 * @param int                   $flavorFlags Flavor flags to use for the entity rdf builder
	 * @param RdfVocabulary         $vocabulary
	 * @param RdfWriter             $writer
	 * @param EntityMentionListener $mentionedEntityTracker
	 * @param DedupeBag             $dedupe
	 * @return DispatchingEntityRdfBuilder
	 */
	public function getEntityRdfBuilder(
		$flavorFlags,
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		EntityMentionListener $mentionedEntityTracker,
		DedupeBag $dedupe
	) {
		$builders = $this->createEntityRdfBuilders(
			$flavorFlags,
			$vocabulary,
			$writer,
			$mentionedEntityTracker,
			$dedupe
		);

		return new DispatchingEntityRdfBuilder( $builders );
	}

	/**
	 * @param int                   $flavorFlags Flavor flags to use for the entity rdf builder
	 * @param RdfVocabulary         $vocabulary
	 * @param RdfWriter             $writer
	 * @param EntityMentionListener $mentionedEntityTracker
	 * @param DedupeBag             $dedupe
	 *
	 * @return EntityRdfBuilder[]
	 */
	private function createEntityRdfBuilders(
		$flavorFlags,
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		EntityMentionListener $mentionedEntityTracker,
		DedupeBag $dedupe
	) {
		$builders = [];

		foreach ( $this->factoryCallbacks as $key => $callback ) {
			$entityBuilders = call_user_func(
				$callback,
				$flavorFlags,
				$vocabulary,
				$writer,
				$mentionedEntityTracker,
				$dedupe
			);

			$builders[$key] = $entityBuilders;
		}

		return $builders;
	}

}
