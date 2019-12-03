<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\ChangeOp\NullChangeOp;

/**
 * Constructs ChangeOps for fingerprint (terms) change requests
 *
 * @license GPL-2.0-or-later
 */
class FingerprintChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @var FingerprintChangeOpFactory
	 */
	private $fingerprintChangeOpFactory;

	/** @var LabelsChangeOpDeserializer */
	private $labelsChangeOpDeserializer;

	/** @var DescriptionsChangeOpDeserializer */
	private $descriptionsChangeOpDeserializer;

	/** @var AliasesChangeOpDeserializer */
	private $aliasesChangeOpDeserializer;

	public function __construct(
	  LabelsChangeOpDeserializer $labelsChangeOpDeserializer,
	  DescriptionsChangeOpDeserializer $descriptionsChangeOpDeserializer,
	  AliasesChangeOpDeserializer $aliasesChangeOpDeserializer,
		FingerprintChangeOpFactory $fingerprintChangeOpFactory
	) {
		$this->labelsChangeOpDeserializer = $labelsChangeOpDeserializer;
		$this->descriptionsChangeOpDeserializer = $descriptionsChangeOpDeserializer;
		$this->aliasesChangeOpDeserializer = $aliasesChangeOpDeserializer;
		$this->fingerprintChangeOpFactory = $fingerprintChangeOpFactory;
	}

	public function createEntityChangeOp( array $changeRequest ) {
		$changeOps = [];

		if ( array_key_exists( 'labels', $changeRequest ) ) {
			$changeOps[] = $this->labelsChangeOpDeserializer->createEntityChangeOp( $changeRequest );
		}

		if ( array_key_exists( 'descriptions', $changeRequest ) ) {
			$changeOps[] = $this->descriptionsChangeOpDeserializer->createEntityChangeOp( $changeRequest );
		}

		if ( array_key_exists( 'aliases', $changeRequest ) ) {
			$changeOps[] = $this->aliasesChangeOpDeserializer->createEntityChangeOp( $changeRequest );
		}

		if ( count( $changeOps ) > 0 ) {
			return $this->fingerprintChangeOpFactory->newFingerprintChangeOp( new ChangeOps( $changeOps ) );
		}

		return new NullChangeOp();
	}
}
