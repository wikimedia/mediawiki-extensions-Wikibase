<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Deserializers\Deserializer;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\StringNormalizer;

/**
 * Factory providing ChangeOpDeserializers for fields of items and properties,
 * such as label, description, alias, claim and sitelink.
 *
 * @license GPL-2.0+
 */
class ChangeOpDeserializerFactory {

	/**
	 * @var FingerprintChangeOpFactory
	 */
	private $fingerprintChangeOpFactory;

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @var SiteLinkChangeOpFactory
	 */
	private $siteLinkChangeOpFactory;

	/**
	 * @var TermChangeOpSerializationValidator
	 */
	private $termChangeOpSerializationValidator;

	/**
	 * @var SiteLinkChangeOpSerializationValidator
	 */
	private $siteLinkChangeOpSerializationValidator;

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

	/**
	 * @var SiteLinkTargetProvider
	 */
	private $siteLinkTargetProvider;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	/**
	 * @param FingerprintChangeOpFactory $fingerprintChangeOpFactory
	 * @param StatementChangeOpFactory $statementChangeOpFactory
	 * @param SiteLinkChangeOpFactory $siteLinkChangeOpFactory
	 * @param TermChangeOpSerializationValidator $termChangeOpSerializationValidator
	 * @param SiteLinkChangeOpSerializationValidator $siteLinkChangeOpSerializationValidator
	 * @param Deserializer $statementDeserializer
	 * @param SiteLinkTargetProvider $siteLinkTargetProvider
	 * @param EntityIdParser $entityIdParser
	 * @param StringNormalizer $stringNormalizer
	 * @param string[] $siteLinkGroups
	 */
	public function __construct(
		FingerprintChangeOpFactory $fingerprintChangeOpFactory,
		StatementChangeOpFactory $statementChangeOpFactory,
		SiteLinkChangeOpFactory $siteLinkChangeOpFactory,
		TermChangeOpSerializationValidator $termChangeOpSerializationValidator,
		SiteLinkChangeOpSerializationValidator $siteLinkChangeOpSerializationValidator,
		Deserializer $statementDeserializer,
		SiteLinkTargetProvider $siteLinkTargetProvider,
		EntityIdParser $entityIdParser,
		StringNormalizer $stringNormalizer,
		array $siteLinkGroups
	) {
		$this->fingerprintChangeOpFactory = $fingerprintChangeOpFactory;
		$this->statementChangeOpFactory = $statementChangeOpFactory;
		$this->siteLinkChangeOpFactory = $siteLinkChangeOpFactory;
		$this->termChangeOpSerializationValidator = $termChangeOpSerializationValidator;
		$this->siteLinkChangeOpSerializationValidator = $siteLinkChangeOpSerializationValidator;
		$this->statementDeserializer = $statementDeserializer;
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
		$this->entityIdParser = $entityIdParser;
		$this->stringNormalizer = $stringNormalizer;
		$this->siteLinkGroups = $siteLinkGroups;
	}

	public function getLabelsChangeOpDeserializer() {
		return new LabelsChangeOpDeserializer(
			$this->fingerprintChangeOpFactory,
			$this->stringNormalizer,
			$this->termChangeOpSerializationValidator
		);
	}

	public function getDescriptionsChangeOpDeserializer() {
		return new DescriptionsChangeOpDeserializer(
			$this->fingerprintChangeOpFactory,
			$this->stringNormalizer,
			$this->termChangeOpSerializationValidator
		);
	}

	public function getAliasesChangeOpDeserializer() {
		return new AliasesChangeOpDeserializer(
			$this->fingerprintChangeOpFactory,
			$this->stringNormalizer,
			$this->termChangeOpSerializationValidator
		);
	}

	public function getClaimsChangeOpDeserializer() {
		return new ClaimsChangeOpDeserializer(
			$this->statementDeserializer,
			$this->statementChangeOpFactory
		);
	}

	public function getSiteLinksChangeOpDeserializer() {
		return new SiteLinksChangeOpDeserializer(
			$this->siteLinkChangeOpSerializationValidator,
			$this->siteLinkChangeOpFactory,
			$this->siteLinkTargetProvider,
			$this->entityIdParser,
			$this->stringNormalizer,
			$this->siteLinkGroups
		);
	}

}
