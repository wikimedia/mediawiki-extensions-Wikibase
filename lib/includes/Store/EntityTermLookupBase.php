<?php

namespace Wikibase\Lib\Store;

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\TermIndexEntry;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityTermLookupBase implements TermLookup {

	/**
	 * @see TermLookup::getLabel
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException
	 * @return string|null
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		$baseClassName = $this->getBaseClassNameForLogging();
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			"wikibase.repo.wb_terms.select.{$baseClassName}_getLabel"
		);

		$labels = $this->getLabels( $entityId, [ $languageCode ] );

		if ( isset( $labels[$languageCode] ) ) {
			return $labels[$languageCode];
		}

		return null;
	}

	/**
	 * @see TermLookup::getLabels
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The languages to get terms for
	 *
	 * @throws TermLookupException
	 * @return string[]
	 */
	public function getLabels( EntityId $entityId, array $languageCodes ) {
		$baseClassName = $this->getBaseClassNameForLogging();
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			"wikibase.repo.wb_terms.select.{$baseClassName}_getLabels"
		);

		return $this->getTermsOfType( $entityId, 'label', $languageCodes );
	}

	/**
	 * @see TermLookup::getDescription
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException
	 * @return string|null
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		$baseClassName = $this->getBaseClassNameForLogging();
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			"wikibase.repo.wb_terms.select.{$baseClassName}_getDescription"
		);
		$descriptions = $this->getDescriptions( $entityId, [ $languageCode ] );

		if ( isset( $descriptions[$languageCode] ) ) {
			return $descriptions[$languageCode];
		}

		return null;
	}

	/**
	 * @see TermLookup::getDescriptions
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The languages to get terms for
	 *
	 * @throws TermLookupException
	 * @return string[]
	 */
	public function getDescriptions( EntityId $entityId, array $languageCodes ) {
		$baseClassName = $this->getBaseClassNameForLogging();
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			"wikibase.repo.wb_terms.select.{$baseClassName}_getDescriptions"
		);

		return $this->getTermsOfType( $entityId, 'description', $languageCodes );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string[] $languageCodes The languages to get terms for
	 *
	 * @return string[]
	 */
	abstract protected function getTermsOfType( EntityId $entityId, $termType, array $languageCodes );

	/**
	 * @param TermIndexEntry[] $wikibaseTerms
	 *
	 * @return string[] strings keyed by language code
	 */
	protected function convertTermsToMap( array $wikibaseTerms ) {
		$terms = [];

		foreach ( $wikibaseTerms as $wikibaseTerm ) {
			$languageCode = $wikibaseTerm->getLanguage();
			$terms[$languageCode] = $wikibaseTerm->getText();
		}

		return $terms;
	}

	/**
	 * @return string
	 */
	private function getBaseClassNameForLogging() {
		$classNameParts = explode( '\\', get_class( $this ) );
		return array_pop( $classNameParts );
	}

}
