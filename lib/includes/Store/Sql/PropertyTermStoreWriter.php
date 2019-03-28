<?php

namespace Wikibase\Lib\Store\Sql;

use Psr\Log\LoggerInterface;
use DBAccessBase;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @license GPL-2.0-or-later
 */
class PropertyTermStoreWriter extends DBAccessBase implements EntityTermStoreWriter {

	const TERM_TYPE_LABEL = 'label';
	const TERM_TYPE_DESCRIPTION = 'description';
	const TERM_TYPE_ALIAS = 'alias';

	const DB_TABLE_TYPE = 'wbt_type';
	const DB_TABLE_TEXT = 'wbt_text';
	const DB_TABLE_TEXT_IN_LANG = 'wbt_text_in_lang';
	const DB_TABLE_TERM_IN_LANG = 'wbt_term_in_lang';
	const DB_TABLE_PROPERTY_TERMS = 'wbt_property_terms';

	/**
	 * @var string
	 */
	private $repositoryName;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var EntitySource
	 */
	private $entitySource;

	/**
	 * @var DataAccessSettings
	 */
	private $dataAccessSettings;

	/**
	 * @param EntitySource $entitySource
	 * @param DataAccessSettings $dataAccessSettings
	 * @param LoggerInterface $logger
	 * @param string|bool $wikiDb
	 * @param string $repositoryName
	 */
	public function __construct(
		EntitySource $entitySource,
		DataAccessSettings $dataAccessSettings,
		LoggerInterface $logger,
		$wikiDb = false,
		$repositoryName = ''
	) {
		RepositoryNameAssert::assertParameterIsValidRepositoryName( $repositoryName, '$repositoryName' );

		$databaseName = $dataAccessSettings->useEntitySourceBasedFederation()
					  ? $entitySource->getDatabaseName()
					  : $wikiDb;

		parent::__construct( $databaseName );

		$this->repositoryName = $repositoryName;
		$this->entitySource = $entitySource;
		$this->dataAccessSettings = $dataAccessSettings;
		$this->logger = $logger;
		$this->db = $this->getConnection( ILoadBalancer::DB_MASTER );
	}

	/**
	 * Saves the terms of the provided property in the term store.
	 *
	 * @param Property $property
	 */
	public function saveTerms( EntityDocument $property ) {
		Assert::parameterType( Property::class, $property, '$property' );

		foreach ( $property->getLabels() as $label ) {
			$this->insertTerm(
				$property->getId(),
				self::TERM_TYPE_LABEL,
				$label->getLanguageCode(),
				$label->getText()
			);
		}
		foreach ( $property->getDescriptions() as $description ) {
			$this->insertTerm(
				$property->getId(),
				self::TERM_TYPE_DESCRIPTION,
				$description->getLanguageCode(),
				$description->getText()
			);
		}
		foreach ( $property->getAliasGroups() as $aliasGroup ) {
			$groupLang = $aliasGroup->getLanguageCode();
			foreach ( $aliasGroup->getAliases() as $aliasText ) {
				$this->insertTerm(
					$property->getId(),
					self::TERM_TYPE_ALIAS,
					$groupLang,
					$aliasText
				);
			}
		}
	}

	/**
	 * Deletes the terms of the provided property from the term store.
	 *
	 * @param PropertyId $propertyId
	 */
	public function deleteTerms( EntityId $propertyId ) {
		Assert::parameterType( PropertyId::class, $propertyId, '$propertyId' );
	}

	private function insertTerm( PropertyId $propertyId, $type, $lang, $text ) {
		$textId = $this->acquireTextId( $text );
		$textInLang = $this->acquireTextInLangId( $lang, $textId );
		$typeId = $this->acquireTypeId( $type );
		$termInLangId = $this->acquireTermInLang( $typeId, $textInLangId );
		$this->insertPropertyTerm( $propertyId, $termInLangId );
	}

	private function acquireTextId( $text ) {
		$this->db->insert(
			self::DB_TABLE_TEXT,
			[ 'wbx_text' => $text ],
			__METHOD__,
			[ 'IGNORE' => true ]
		);

		return $this->db->selectField(
			self::DB_TABLE_TEXT,
			'wbx_id',
			[ 'wbx_text' => $text ]
		);
	}

	private function acquireTextInLangId( $lang, $textId ) {
		$this->db->insert(
			self::DB_TABLE_TEXT_IN_LANG,
			[ 'wbxl_language' => $lang, 'wbxl_text_id' => $textId ],
			__METHOD__,
			[ 'IGNORE' => true ]
		);

		return $this->db->selectField(
			self::DB_TABLE_TEXT_IN_LANG,
			'wbxl_id',
			[ 'wbxl_language' => $lang, 'wbxl_text_id' => $textId ]
		);
	}

	private function acquireTypeId( $typeName ) {
		$this->db->insert(
			self::DB_TABLE_TYPE,
			[ 'wby_name' => $typeName ],
			__METHOD__,
			[ 'IGNORE' => true ]
		);

		return $this->db->selectField(
			self::DB_TABLE_TYPE,
			'wby_id',
			[ 'wby_name' => $typeName ]
		);
	}

	private function acquireTermInLang( $typeId, $textInLangId ) {
		$this->db->insert(
			self::DB_TABLE_TERM_IN_LANG,
			[
				'wbtl_type_id' => $typeId,
				'wbtl_text_in_lang_id' => $textInLangId
			],
			__METHOD__,
			[ 'IGNORE' => true ]
		);

		return $this->db->selectField(
			self::DB_TABLE_TERM_IN_LANG,
			'wbtl_id',
			[
				'wbtl_type_id' => $typeId,
				'wbtl_text_in_lang_id' => $textInLangId
			]
		);
	}

	private function insertProeprtyTerm( PropertyId $propertyId, $termInLangId ) {
		$pid = $propertyId->getNumericPart();
		$this->db->insert(
			self::DB_TABLE_PROPERTY_TERMS,
			[
				'wbpt_property_id' => $pid,
				'wbpt_term_in_lang_id' => $termInLangId
			]
		);
	}
}
