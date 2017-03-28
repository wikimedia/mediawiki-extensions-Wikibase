<?php

namespace Wikibase\Repo\Diff;

use IContextSource;
use SiteLookup;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikimedia\Assert\Assert;

/**
 * Turns entity change request into ChangeOp objects based on change request deserialization
 * configured for the particular entity type.
 *
 * @license GPL-2.0+
 */
class EntityDiffVisualizerFactory {

	/**
	 * @var callable[]
	 */
	private $entityDiffVisualizerInstantiators;

	/**
	 * @param callable[] $entityDiffVisualizerInstantiators Associative array mapping entity types (strings)
	 * to callbacks instantiating EntityDiffVisualizer objects.
	 */
	public function __construct( array $entityDiffVisualizerInstantiators ) {
		Assert::parameterElementType( 'callable', $entityDiffVisualizerInstantiators, '$entityDiffVisualizerInstantiators' );
		Assert::parameterElementType(
			'string',
			array_keys( $entityDiffVisualizerInstantiators ),
			'array_keys( $entityDiffVisualizerInstantiators )'
		);

		$this->entityDiffVisualizerInstantiators = $entityDiffVisualizerInstantiators;
	}

	/**
	 * @param string $type
	 *
	 * @param IContextSource $contextSource
	 * @param ClaimDiffer $claimDiffer
	 * @param ClaimDifferenceVisualizer $claimDiffView
	 * @param SiteLookup $siteLookup
	 * @param EntityIdFormatter $entityIdFormatter
	 *
	 * @return null|EntityDiffVisualizer
	 */
	public function newEntityDiffVisualizer(
		$type,
		IContextSource $contextSource,
		ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffView,
		SiteLookup $siteLookup,
		EntityIdFormatter $entityIdFormatter
	) {
		if ( !array_key_exists( $type, $this->entityDiffVisualizerInstantiators ) ) {
			return null;
		}

		$visualizer = call_user_func(
			$this->entityDiffVisualizerInstantiators[$type],
			$contextSource,
			$claimDiffer,
			$claimDiffView,
			$siteLookup,
			$entityIdFormatter
		);
		Assert::postcondition(
			$visualizer instanceof EntityDiffVisualizer,
			'entity-diff-visualizer-callback defined for entity type: ' . $type . ' does not instantiate EntityDiffVisualizer'
		);

		return $visualizer;
	}

}
