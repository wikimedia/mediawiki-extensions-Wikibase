<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;

/**
 * @since 1.1
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Addshore
 */
class EntityRetrievingTermLookup implements TermLookup {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var TermList[] Labels in all languages, indexed by entity ID serialization.
	 */
	private $labels;

	/**
	 * @var TermList[] Descriptions in all languages, indexed by entity ID serialization.
	 */
	private $descriptions;

	public function __construct( EntityLookup $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @see TermLookup::getLabel()
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @return string|null
	 * @throws TermLookupException
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		$labels = $this->getAllLabels( $entityId, [ $languageCode ] );

		if ( $labels->hasTermForLanguage( $languageCode ) ) {
			return $labels->getByLanguage( $languageCode )->getText();
		}

		return null;
	}

	/**
	 * @see TermLookup::getLabels()
	 *
	 * @param EntityId $entityId
	 * @param string[] $languages
	 *
	 * @throws TermLookupException
	 * @return string[]
	 */
	public function getLabels( EntityId $entityId, array $languages ) {
		$labels = $this->getAllLabels( $entityId, $languages )->toTextArray();

		return array_intersect_key( $labels, array_flip( $languages ) );
	}

	/**
	 * @see TermLookup::getDescription()
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException
	 * @return string|null
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		$descriptions = $this->getAllDescriptions( $entityId, [ $languageCode ] );

		if ( $descriptions->hasTermForLanguage( $languageCode ) ) {
			return $descriptions->getByLanguage( $languageCode )->getText();
		}

		return null;
	}

	/**
	 * @see TermLookup::getDescriptions()
	 *
	 * @param EntityId $entityId
	 * @param string[] $languages
	 *
	 * @throws TermLookupException
	 * @return string[]
	 */
	public function getDescriptions( EntityId $entityId, array $languages ) {
		$descriptions = $this->getAllDescriptions( $entityId, $languages )->toTextArray();

		return array_intersect_key( $descriptions, array_flip( $languages ) );
	}

	/**
	 * @param EntityId $entityId
	 * @param string[] $languageCodes Not used for filtering but in thrown exceptions.
	 *
	 * @throws TermLookupException
	 * @return TermList
	 */
	private function getAllLabels( EntityId $entityId, array $languageCodes ) {
		$id = $entityId->getSerialization();

		if ( !isset( $this->labels[$id] ) ) {
			$entity = $this->fetchEntity( $entityId, $languageCodes );
			$this->labels[$id] = $entity instanceof LabelsProvider
				? $entity->getLabels()
				: new TermList();
		}

		return $this->labels[$id];
	}

	/**
	 * @param EntityId $entityId
	 * @param string[] $languageCodes Not used for filtering but in thrown exceptions.
	 *
	 * @throws TermLookupException
	 * @return TermList
	 */
	private function getAllDescriptions( EntityId $entityId, array $languageCodes ) {
		$id = $entityId->getSerialization();

		if ( !isset( $this->descriptions[$id] ) ) {
			$entity = $this->fetchEntity( $entityId, $languageCodes );
			$this->descriptions[$id] = $entity instanceof DescriptionsProvider
				? $entity->getDescriptions()
				: new TermList();
		}

		return $this->descriptions[$id];
	}

	/**
	 * @param EntityId $entityId
	 * @param string[] $languages Not used for filtering but in thrown exceptions.
	 *
	 * @throws TermLookupException
	 * @return EntityDocument
	 */
	private function fetchEntity( EntityId $entityId, array $languages ) {
		try {
			$entity = $this->entityLookup->getEntity( $entityId );
		} catch ( EntityLookupException $ex ) {
			throw new TermLookupException( $entityId, $languages, 'The entity could not be loaded', $ex );
		}

		if ( $entity === null ) {
			throw new TermLookupException( $entityId, $languages, 'The entity could not be loaded' );
		}

		return $entity;
	}

}
