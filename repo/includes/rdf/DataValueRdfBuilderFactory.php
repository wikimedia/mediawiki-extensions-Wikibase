<?php

namespace Wikibase\Rdf;

use DataValues\DataValue;
use Wikimedia\Assert\Assert;
use Wikimedia\Purtle\RdfWriter;

/**
 * Factory for DataValueRdfBuilder based on factory callbacks.
 * For use with DataTypeDefinitions.
 *
 * @todo FIXME: test case!
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DataValueRdfBuilderFactory {

	/**
	 * @var callable[]
	 */
	private $factoryCallbacks;

	/**
	 * @param callable[] factory callback functions as returned by
	 *        DataTypeDefinitions::getRdfBuilderFactoryCallbacks(). Callbacks will be invoked
	 *        with the signature ($mode, RdfVocabulary, EntityMentionListener) and must
	 *        return a DataValueRdfBuilder (or null).
	 */
	public function __construct( array $factoryCallbacks ) {
		Assert::parameterElementType( 'callable', $factoryCallbacks, '$factoryCallbacks' );

		$this->factoryCallbacks = $factoryCallbacks;
	}


	/**
	 * Returns an DataValueRdfBuilder for simple value output.
	 *
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $writer
	 * @param EntityMentionListener $mentionedEntityTracker
	 * @param DedupeBag $dedupe
	 *
	 * @return DispatchingValueRdfBuilder
	 */
	public function getSimpleDataValueRdfBuilder(
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		EntityMentionListener $mentionedEntityTracker,
		DedupeBag $dedupe
	) {
		$builders = $this->createDataValueRdfBuilders(
			'simple',
			$vocabulary,
			$writer,
			$mentionedEntityTracker,
			$dedupe
		);

		return new DispatchingValueRdfBuilder( $builders );
	}

	/**
	 * Returns an DataValueRdfBuilder for reified value output.
	 *
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $writer
	 * @param EntityMentionListener $mentionedEntityTracker
	 * @param DedupeBag $dedupe
	 *
	 * @return DispatchingValueRdfBuilder
	 */
	public function getComplexDataValueRdfBuilder(
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		EntityMentionListener $mentionedEntityTracker,
		DedupeBag $dedupe
	) {
		$builders = $this->createDataValueRdfBuilders(
			'complex',
			$vocabulary,
			$writer,
			$mentionedEntityTracker,
			$dedupe
		);

		return new DispatchingValueRdfBuilder( $builders );
	}

	/**
	 * @param string $mode
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $writer
	 * @param EntityMentionListener $mentionedEntityTracker
	 * @param DedupeBag $dedupe
	 *
	 * @return DataValueRdfBuilder[]
	 */
	private function createDataValueRdfBuilders(
		$mode,
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		EntityMentionListener $mentionedEntityTracker,
		DedupeBag $dedupe
	) {
		$builders = array();

		foreach ( $this->factoryCallbacks as $key => $callback ) {
			$builder = call_user_func(
				$callback,
				$mode,
				$vocabulary,
				$writer,
				$mentionedEntityTracker,
				$dedupe
			);

			if ( $builder instanceof DataValueRdfBuilder ) {
				$builders[$key] = $builder;
			}
		}

		return $builders;

	}
}
