<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Deserializers\Deserializer;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;

/**
 * Factory providing ChangeOpDeserializers for fields of Wikibase
 * entities such as label, description, alias, claim and sitelink
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
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

	/**
	 * @var TermChangeOpSerializationValidator()
	 */
	private $validator;

	public function __construct() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

		$this->fingerprintChangeOpFactory = $changeOpFactoryProvider->getFingerprintChangeOpFactory();
		$this->stringNormalizer = $wikibaseRepo->getStringNormalizer();
		$this->statementChangeOpFactory = $changeOpFactoryProvider->getStatementChangeOpFactory();
		$this->statementDeserializer = $wikibaseRepo->getExternalFormatStatementDeserializer();
		$this->validator = new TermChangeOpSerializationValidator( $wikibaseRepo->getTermsLanguages() );
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
	 * @return DescriptionsChangeOpDeserializer
	 */
	public function getDescriptionsChangeOpDeserializer() {
		return new DescriptionsChangeOpDeserializer(
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
			$this->statementDeserializer,
			$this->statementChangeOpFactory
		);
	}

	public function getSiteLinksChangeOpDeserializer() {
		// TODO
	}

}
