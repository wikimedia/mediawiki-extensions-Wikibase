<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Deserializers\Deserializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\SiteLinkPageNormalizer;
use Wikibase\Repo\SiteLinkTargetProvider;

/**
 * Factory providing ChangeOpDeserializers for fields of items and properties,
 * such as label, description, alias, claim and sitelink.
 *
 * @license GPL-2.0-or-later
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
	 * @var SiteLinkBadgeChangeOpSerializationValidator
	 */
	private $badgeChangeOpSerializationValidator;

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

	/** @var SiteLinkPageNormalizer */
	private $siteLinkPageNormalizer;

	/**
	 * @var SiteLinkTargetProvider
	 */
	private $siteLinkTargetProvider;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

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
	 * @param SiteLinkBadgeChangeOpSerializationValidator $badgeChangeOpSerializationValidator
	 * @param Deserializer $statementDeserializer
	 * @param SiteLinkPageNormalizer $siteLinkPageNormalizer
	 * @param SiteLinkTargetProvider $siteLinkTargetProvider
	 * @param EntityIdParser $entityIdParser
	 * @param EntityLookup $entityLookup
	 * @param StringNormalizer $stringNormalizer
	 * @param string[] $siteLinkGroups
	 */
	public function __construct(
		FingerprintChangeOpFactory $fingerprintChangeOpFactory,
		StatementChangeOpFactory $statementChangeOpFactory,
		SiteLinkChangeOpFactory $siteLinkChangeOpFactory,
		TermChangeOpSerializationValidator $termChangeOpSerializationValidator,
		SiteLinkBadgeChangeOpSerializationValidator $badgeChangeOpSerializationValidator,
		Deserializer $statementDeserializer,
		SiteLinkPageNormalizer $siteLinkPageNormalizer,
		SiteLinkTargetProvider $siteLinkTargetProvider,
		EntityIdParser $entityIdParser,
		EntityLookup $entityLookup,
		StringNormalizer $stringNormalizer,
		array $siteLinkGroups
	) {
		$this->fingerprintChangeOpFactory = $fingerprintChangeOpFactory;
		$this->statementChangeOpFactory = $statementChangeOpFactory;
		$this->siteLinkChangeOpFactory = $siteLinkChangeOpFactory;
		$this->termChangeOpSerializationValidator = $termChangeOpSerializationValidator;
		$this->badgeChangeOpSerializationValidator = $badgeChangeOpSerializationValidator;
		$this->statementDeserializer = $statementDeserializer;
		$this->siteLinkPageNormalizer = $siteLinkPageNormalizer;
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
		$this->entityIdParser = $entityIdParser;
		$this->entityLookup = $entityLookup;
		$this->stringNormalizer = $stringNormalizer;
		$this->siteLinkGroups = $siteLinkGroups;
	}

	public function getFingerprintChangeOpDeserializer() {
		return new FingerprintChangeOpDeserializer(
			$this->getLabelsChangeOpDeserializer(),
			$this->getDescriptionsChangeOpDeserializer(),
			$this->getAliasesChangeOpDeserializer(),
			$this->fingerprintChangeOpFactory
		);
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
			$this->badgeChangeOpSerializationValidator,
			$this->siteLinkChangeOpFactory,
			$this->siteLinkPageNormalizer,
			$this->siteLinkTargetProvider,
			$this->entityIdParser,
			$this->entityLookup,
			$this->stringNormalizer,
			$this->siteLinkGroups
		);
	}

}
