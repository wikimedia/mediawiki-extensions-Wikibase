<?php

namespace Wikibase\Repo\ChangeOpDeserializers;

use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\WikibaseRepo;

// TODO: find a more specific name
class ChangeOpDeserializerFactory {
	private $fingerprintChangeOpFactory;

	private $stringNormalizer;

	private $errorReporter;

	private $statementChangeOpFactory;

	private $statementDeserializer;

	public function __construct() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

		$this->fingerprintChangeOpFactory = $changeOpFactoryProvider->getFingerprintChangeOpFactory();
		$this->stringNormalizer = $wikibaseRepo->getStringNormalizer();
		$this->errorReporter = new ApiErrorReporter( null, null, null ); // FIXME: figure out how to inject this
		$this->statementChangeOpFactory = $changeOpFactoryProvider->getStatementChangeOpFactory();
		$this->statementDeserializer = $wikibaseRepo->getExternalFormatStatementDeserializer();
	}

	public function getLabelsChangeOpDeserializer() {
		return new LabelsChangeOpDeserializer(
			$this->fingerprintChangeOpFactory,
			$this->stringNormalizer
		);
	}

	public function getDescriptionsChangeOpDeserializer() {
		return new DescriptionChangeOpDeserializer(
			$this->fingerprintChangeOpFactory,
			$this->stringNormalizer
		);
	}

	public function getAliasesChangeOpDeserializer() {
		return new AliasesChangeOpDeserializer(
			$this->fingerprintChangeOpFactory,
			$this->stringNormalizer
		);
	}

	public function getClaimsChangeOpDeserializer() {
		return new ClaimsChangeOpDeserializer(
			$this->errorReporter,
			$this->statementDeserializer,
			$this->statementChangeOpFactory
		);
	}

}
