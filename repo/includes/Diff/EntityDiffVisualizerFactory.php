<?php

namespace Wikibase\Repo\Diff;

use IContextSource;
use RequestContext;
use SiteLookup;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\View\EntityIdFormatterFactory;
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
	 * @var ClaimDiffer
	 */
	private $claimDiffer;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;
	/**
	 * @var EntityIdFormatterFactory
	 */
	private $entityIdFormatterFactory;

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @param callable[] $entityDiffVisualizerInstantiators Associative array mapping entity types (strings)
	 * to callbacks instantiating EntityDiffVisualizer objects.
	 * @param ClaimDiffer $claimDiffer
	 * @param SiteLookup $siteLookup
	 * @param EntityIdFormatterFactory $entityIdFormatterFactory
	 * @param OutputFormatSnakFormatterFactory $snakFormatterFactory
	 */
	public function __construct(
		array $entityDiffVisualizerInstantiators,
		ClaimDiffer $claimDiffer,
		SiteLookup $siteLookup,
		EntityIdFormatterFactory $entityIdFormatterFactory,
		OutputFormatSnakFormatterFactory $snakFormatterFactory
	) {
		Assert::parameterElementType( 'callable', $entityDiffVisualizerInstantiators, '$entityDiffVisualizerInstantiators' );
		Assert::parameterElementType(
			'string',
			array_keys( $entityDiffVisualizerInstantiators ),
			'array_keys( $entityDiffVisualizerInstantiators )'
		);

		$this->entityDiffVisualizerInstantiators = $entityDiffVisualizerInstantiators;
		$this->claimDiffer = $claimDiffer;
		$this->siteLookup = $siteLookup;
		$this->entityIdFormatterFactory = $entityIdFormatterFactory;
		$this->snakFormatterFactory = $snakFormatterFactory;
	}

	public function newEntityDiffVisualizer( ?string $type = null, ?IContextSource $context = null ): EntityDiffVisualizer {
		if ( $context === null ) {
			$context = RequestContext::getMain();
		}
		$langCode = $context->getLanguage()->getCode();
		$options = new FormatterOptions( [
			//TODO: fallback chain
			ValueFormatter::OPT_LANG => $langCode,
		] );
		$entityIdFormatter = $this->entityIdFormatterFactory->getEntityIdFormatter( $context->getLanguage() );
		$diffSnakView = new DifferencesSnakVisualizer(
			$entityIdFormatter,
			$this->snakFormatterFactory->getSnakFormatter( SnakFormatter::FORMAT_HTML_DIFF, $options ),
			$this->snakFormatterFactory->getSnakFormatter( SnakFormatter::FORMAT_HTML, $options ),
			$langCode
		);
		$claimDiffView = new ClaimDifferenceVisualizer( $diffSnakView, $langCode );

		if ( $type === null || !array_key_exists( $type, $this->entityDiffVisualizerInstantiators )
		) {
			return new BasicEntityDiffVisualizer(
				$context,
				$this->claimDiffer,
				$claimDiffView
			);
		}

		$visualizer = call_user_func(
			$this->entityDiffVisualizerInstantiators[$type],
			$context,
			$this->claimDiffer,
			$claimDiffView,
			$this->siteLookup,
			$entityIdFormatter
		);
		Assert::postcondition(
			$visualizer instanceof EntityDiffVisualizer,
			'entity-diff-visualizer-callback defined for entity type: ' . $type . ' does not instantiate EntityDiffVisualizer'
		);

		return $visualizer;
	}
}
