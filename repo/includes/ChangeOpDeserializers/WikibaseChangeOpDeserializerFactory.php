<?php

namespace Wikibase\Repo\ChangeOpDeserializers;

use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Tests\Validators\TermChangeOpValidator;
use Wikibase\Repo\WikibaseRepo;

class WikibaseChangeOpDeserializerFactory {

	private $fingerprintChangeOpFactory;

	private $stringNormalizer;

	private $errorReporter;

	private $statementChangeOpFactory;

	private $statementDeserializer;

	private $validator;

	public function __construct( ApiErrorReporter $errorReporter ) {
		$this->errorReporter = $errorReporter;

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

		$this->fingerprintChangeOpFactory = $changeOpFactoryProvider->getFingerprintChangeOpFactory();
		$this->stringNormalizer = $wikibaseRepo->getStringNormalizer();
		$this->statementChangeOpFactory = $changeOpFactoryProvider->getStatementChangeOpFactory();
		$this->statementDeserializer = $wikibaseRepo->getExternalFormatStatementDeserializer();
		$this->validator = new TermChangeOpValidator( $this->errorReporter, $wikibaseRepo->getTermsLanguages() );
	}

	public function getLabelsChangeOpDeserializer() {
		return new LabelsChangeOpDeserializer(
			$this->fingerprintChangeOpFactory,
			$this->stringNormalizer,
			$this->validator
		);
	}

	public function getDescriptionsChangeOpDeserializer() {
		return new DescriptionChangeOpDeserializer(
			$this->fingerprintChangeOpFactory,
			$this->stringNormalizer,
			$this->validator
		);
	}

	public function getAliasesChangeOpDeserializer() {
		return new AliasesChangeOpDeserializer(
			$this->fingerprintChangeOpFactory,
			$this->stringNormalizer,
			$this->validator
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
