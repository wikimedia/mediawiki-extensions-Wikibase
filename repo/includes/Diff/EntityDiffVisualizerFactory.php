<?php

namespace Wikibase\Repo\Diff;

use MessageLocalizer;
use SiteLookup;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikimedia\Assert\Assert;

/**
 * Turns entity change request into ChangeOp objects based on change request deserialization
 * configured for the particular entity type.
 *
 * @license GPL-2.0-or-later
 */
class EntityDiffVisualizerFactory {

	/**
	 * @var callable[]
	 */
	private $entityDiffVisualizerInstantiators;

	/**
	 * @var MessageLocalizer
	 */
	private $messageLocalizer;

	/**
	 * @var ClaimDiffer
	 */
	private $claimDiffer;

	/**
	 * @var ClaimDifferenceVisualizer
	 */
	private $claimDiffView;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdFormatter;

	/**
	 * @param callable[] $entityDiffVisualizerInstantiators Associative array mapping entity types (strings)
	 * to callbacks instantiating EntityDiffVisualizer objects.
	 * @param MessageLocalizer $messageLocalizer
	 * @param ClaimDiffer $claimDiffer
	 * @param ClaimDifferenceVisualizer $claimDiffView
	 * @param SiteLookup $siteLookup
	 * @param EntityIdFormatter $entityIdFormatter
	 */
	public function __construct(
		array $entityDiffVisualizerInstantiators,
		MessageLocalizer $messageLocalizer,
		ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffView,
		SiteLookup $siteLookup,
		EntityIdFormatter $entityIdFormatter
	) {
		Assert::parameterElementType( 'callable', $entityDiffVisualizerInstantiators, '$entityDiffVisualizerInstantiators' );
		Assert::parameterElementType(
			'string',
			array_keys( $entityDiffVisualizerInstantiators ),
			'array_keys( $entityDiffVisualizerInstantiators )'
		);

		$this->entityDiffVisualizerInstantiators = $entityDiffVisualizerInstantiators;
		$this->messageLocalizer = $messageLocalizer;
		$this->claimDiffer = $claimDiffer;
		$this->claimDiffView = $claimDiffView;
		$this->siteLookup = $siteLookup;
		$this->entityIdFormatter = $entityIdFormatter;
	}

	/**
	 * @param string|null $type
	 *
	 * @return EntityDiffVisualizer
	 */
	public function newEntityDiffVisualizer( $type = null ) {
		if ( $type === null || !array_key_exists( $type, $this->entityDiffVisualizerInstantiators )
		) {
			return new BasicEntityDiffVisualizer(
				$this->messageLocalizer,
				$this->claimDiffer,
				$this->claimDiffView,
				$this->siteLookup,
				$this->entityIdFormatter
			);
		}

		$visualizer = call_user_func(
			$this->entityDiffVisualizerInstantiators[$type],
			$this->messageLocalizer,
			$this->claimDiffer,
			$this->claimDiffView,
			$this->siteLookup,
			$this->entityIdFormatter
		);
		Assert::postcondition(
			$visualizer instanceof EntityDiffVisualizer,
			'entity-diff-visualizer-callback defined for entity type: ' . $type . ' does not instantiate EntityDiffVisualizer'
		);

		return $visualizer;
	}

}
