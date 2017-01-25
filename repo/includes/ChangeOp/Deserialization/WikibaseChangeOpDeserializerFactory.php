<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Deserializers\Deserializer;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\ChangeOp\StatementChangeOpFactory;
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
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @var TermChangeOpSerializationValidator
	 */
	private $validator;

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	public function __construct(
		FingerprintChangeOpFactory $fingerprintChangeOpFactory,
		StatementChangeOpFactory $statementChangeOpFactory,
		TermChangeOpSerializationValidator $validator,
		Deserializer $statementDeserializer,
		StringNormalizer $stringNormalizer
	) {
		$this->fingerprintChangeOpFactory = $fingerprintChangeOpFactory;
		$this->statementChangeOpFactory = $statementChangeOpFactory;
		$this->validator = $validator;
		$this->statementDeserializer = $statementDeserializer;
		$this->stringNormalizer = $stringNormalizer;
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
