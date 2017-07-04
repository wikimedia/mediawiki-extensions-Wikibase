<?php

namespace Wikibase\Rdf;

use Wikimedia\Assert\Assert;
use Wikimedia\Purtle\RdfWriter;

/**
 * Factory for ValueSnakRdfBuilder based on factory callbacks.
 * For use with DataTypeDefinitions.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ValueSnakRdfBuilderFactory {

	/**
	 * @var callable[]
	 */
	private $factoryCallbacks;

	/**
	 * @param callable[] $factoryCallbacks Factory callback functions as returned by
	 *        DataTypeDefinitions::getRdfBuilderFactoryCallbacks(). Callbacks will be invoked
	 *        with the signature ($mode, RdfVocabulary, EntityMentionListener) and must
	 *        return a ValueSnakRdfBuilder (or null).
	 */
	public function __construct( array $factoryCallbacks ) {
		Assert::parameterElementType( 'callable', $factoryCallbacks, '$factoryCallbacks' );

		$this->factoryCallbacks = $factoryCallbacks;
	}

	/**
	 * Returns an ValueSnakRdfBuilder for reified value output.
	 *
	 * @param int                   $flavorFlags Flavor flags to use for the snak builder
	 * @param RdfVocabulary         $vocabulary
	 * @param RdfWriter             $writer
	 * @param EntityMentionListener $mentionedEntityTracker
	 * @param DedupeBag             $dedupe
	 *
	 * @return DispatchingValueSnakRdfBuilder
	 */
	public function getValueSnakRdfBuilder(
		$flavorFlags,
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		EntityMentionListener $mentionedEntityTracker,
		DedupeBag $dedupe
	) {
		$builders = $this->createValueSnakRdfBuilders(
			$flavorFlags,
			$vocabulary,
			$writer,
			$mentionedEntityTracker,
			$dedupe
		);

		return new DispatchingValueSnakRdfBuilder( $builders );
	}

	/**
	 * @param int                   $flavorFlags Flavor flags to use for the snak builder
	 * @param RdfVocabulary         $vocabulary
	 * @param RdfWriter             $writer
	 * @param EntityMentionListener $mentionedEntityTracker
	 * @param DedupeBag             $dedupe
	 *
	 * @return ValueSnakRdfBuilder[]
	 */
	private function createValueSnakRdfBuilders(
		$flavorFlags,
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		EntityMentionListener $mentionedEntityTracker,
		DedupeBag $dedupe
	) {
		$builders = [];

		foreach ( $this->factoryCallbacks as $key => $callback ) {
			$builder = call_user_func(
				$callback,
				$flavorFlags,
				$vocabulary,
				$writer,
				$mentionedEntityTracker,
				$dedupe
			);

			if ( $builder instanceof ValueSnakRdfBuilder ) {
				$builders[$key] = $builder;
			}
		}

		return $builders;
	}

}
