<?php

namespace Wikibase\Repo\ChangeOpDeserializers;

use Deserializers\Deserializer;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Validators\TermChangeOpValidator;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;

/**
 * TODO: add class description
 *
 * @license GPL-2.0+
 */
class WikibaseChangeOpDeserializerFactory {

	/**
	 * @var FingerprintChangeOpFactory
	 */
	private $fingerprintChangeOpFactory;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

	/**
	 * @var TermChangeOpValidator
	 */
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

	/**
	 * @return LabelsChangeOpDeserializer
	 */
	public function getLabelsChangeOpDeserializer() {
		return new LabelsChangeOpDeserializer(
			$this->fingerprintChangeOpFactory,
			$this->stringNormalizer,
			$this->validator
		);
	}

	/**
	 * @return DescriptionChangeOpDeserializer
	 */
	public function getDescriptionsChangeOpDeserializer() {
		return new DescriptionChangeOpDeserializer(
			$this->fingerprintChangeOpFactory,
			$this->stringNormalizer,
			$this->validator
		);
	}

	/**
	 * @return AliasesChangeOpDeserializer
	 */
	public function getAliasesChangeOpDeserializer() {
		return new AliasesChangeOpDeserializer(
			$this->fingerprintChangeOpFactory,
			$this->stringNormalizer,
			$this->validator
		);
	}

	/**
	 * @return ClaimsChangeOpDeserializer
	 */
	public function getClaimsChangeOpDeserializer() {
		return new ClaimsChangeOpDeserializer(
			$this->errorReporter,
			$this->statementDeserializer,
			$this->statementChangeOpFactory
		);
	}

}
