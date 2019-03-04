<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\LanguageFallbackChain;
use Wikimedia\Assert\Assert;

/**
 * Class for creating meta tags (i.e. title and description) for kinds of Fingerprint Provider
 *
 * @license GPL-2.0-or-later
 */
class FingerprintableEntityMetaTagsCreator implements EntityMetaTagsCreator {

	private $languageFallbackChain;

	public function __construct( LanguageFallbackChain $languageFallbackChain ) {
		$this->languageFallbackChain = $languageFallbackChain;
	}

	public function getMetaTags( EntityDocument $entity ) : array {
		Assert::parameterType( FingerprintProvider::class, $entity, '$entity' );
		/** @var FingerprintProvider $entity */

		$metaTags = [
			'title' => $this->getTitleText( $entity ),
		];

		$description = $this->getDescriptionText( $entity );
		if ( isset( $description ) ) {
			$metaTags['description'] = $description;
		}

		return $metaTags;
	}

	/**
	 * @param FingerprintProvider $entity
	 *
	 * @return string|null
	 */
	private function getDescriptionText( FingerprintProvider $entity ) {
		$descriptions = $entity->getFingerprint()
			->getDescriptions()
			->toTextArray();
		$preferred = $this->languageFallbackChain->extractPreferredValue( $descriptions );

		if ( is_array( $preferred ) ) {
			return $preferred['value'];
		}
		return null;
	}

	/**
	 * @param FingerprintProvider|EntityDocument $entity
	 *
	 * @return string|null
	 */
	private function getTitleText( FingerprintProvider $entity ) {
		$labels = $entity->getFingerprint()
			->getLabels()
			->toTextArray();
		$preferred = $this->languageFallbackChain->extractPreferredValue( $labels );

		if ( is_array( $preferred ) ) {
			return $preferred['value'];
		}

		$entityId = $entity->getId();

		if ( $entityId instanceof EntityId ) {
			return $entityId->getSerialization();
		}

		return null;
	}

}
