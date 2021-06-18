<?php

namespace Wikibase\Repo\Rdf;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikimedia\Assert\Assert;
use Wikimedia\Purtle\RdfWriter;

/**
 * Factory for ValueSnakRdfBuilder based on factory callbacks.
 * For use with DataTypeDefinitions.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ValueSnakRdfBuilderFactory {

	/**
	 * @var callable[]
	 */
	private $factoryCallbacks;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param callable[] $factoryCallbacks Factory callback functions as returned by
	 *        DataTypeDefinitions::getRdfBuilderFactoryCallbacks(). Callbacks will be invoked
	 *        with the signature ($mode, RdfVocabulary, EntityMentionListener) and must
	 *        return a ValueSnakRdfBuilder (or null).
	 * @param LoggerInterface|null $logger
	 */
	public function __construct( array $factoryCallbacks, LoggerInterface $logger = null ) {
		Assert::parameterElementType( 'callable', $factoryCallbacks, '$factoryCallbacks' );

		$this->factoryCallbacks = $factoryCallbacks;
		$this->logger = $logger ?: new NullLogger();
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

		return new DispatchingValueSnakRdfBuilder( $builders, $this->logger );
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
