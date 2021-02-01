<?php

declare( strict_types = 1 );

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikimedia\Assert\Assert;

/**
 * Class for creating meta tags (i.e. title and description) for kinds of Fingerprint Provider
 *
 * @license GPL-2.0-or-later
 */
class FingerprintableEntityMetaTagsCreator implements EntityMetaTagsCreator {

	/** @var TermLanguageFallbackChain */
	private $termLanguageFallbackChain;

	public function __construct( TermLanguageFallbackChain $termLanguageFallbackChain ) {
		$this->termLanguageFallbackChain = $termLanguageFallbackChain;
	}

	/**
	 * @inheritDoc
	 * @suppress PhanTypeMismatchArgument
	 */
	public function getMetaTags( EntityDocument $entity ): array {
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

	private function getDescriptionText( FingerprintProvider $entity ): ?string {
		$descriptions = $entity->getFingerprint()
			->getDescriptions()
			->toTextArray();
		$preferred = $this->termLanguageFallbackChain->extractPreferredValue( $descriptions );

		if ( is_array( $preferred ) ) {
			return $preferred['value'];
		}
		return null;
	}

	/**
	 * @param FingerprintProvider|EntityDocument $entity
	 *
	 * @return string|null
	 * @suppress PhanTypeMismatchDeclaredParam,PhanUndeclaredMethod Intersection type
	 */
	private function getTitleText( FingerprintProvider $entity ): ?string {
		$labels = $entity->getFingerprint()
			->getLabels()
			->toTextArray();
		$preferred = $this->termLanguageFallbackChain->extractPreferredValue( $labels );

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
