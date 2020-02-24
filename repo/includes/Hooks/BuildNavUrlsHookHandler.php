<?php


namespace Wikibase\Repo\Hooks;

use Psr\Log\LoggerInterface;
use SkinTemplate;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class BuildNavUrlsHookHandler {

	/**
	 * @var self
	 */
	private static $instance = null;
	/**
	 * @var string
	 */
	private $baseConceptUri;
	/**
	 * @var EntityIdLookup
	 */
	private $idLookup;
	/**
	 * @var EntityLookup
	 */
	private $entityLookup;
	/**
	 * @var EntityNamespaceLookup
	 */
	private $nsLookup;
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(
		string $baseConceptUri,
		EntityIdLookup $idLookup,
		EntityLookup $entityLookup,
		EntityNamespaceLookup $nsLookup,
		LoggerInterface $logger
	) {

		$this->baseConceptUri = $baseConceptUri;
		$this->idLookup = $idLookup;
		$this->entityLookup = $entityLookup;
		$this->nsLookup = $nsLookup;
		$this->logger = $logger;
	}

	/**
	 * Instantiates the hook handler with services from the WikibaseRepo Singleton
	 *
	 * @return BuildNavUrlsHookHandler
	 */
	public static function newFromGlobalState() {
		$repo = WikibaseRepo::getDefaultInstance();
		return new self(
			$repo->getSettings()->getSetting( 'conceptBaseUri' ),
			$repo->getEntityIdLookup(),
			$repo->getEntityLookup(),
			$repo->getEntityNamespaceLookup(),
			$repo->getLogger()
		);
	}

	/**
	 * Called in SkinTemplate::buildNavUrls(), allows us to set up navigation URLs to later be used
	 * in the toolbox.
	 *
	 * @param SkinTemplate $skinTemplate
	 * @param array[] &$navigationUrls
	 */
	public static function onSkinTemplateBuildNavUrlsNavUrlsAfterPermalink(
		SkinTemplate $skinTemplate,
		array &$navigationUrls
	) {
		$navigationUrls = array_merge( $navigationUrls, self::newFromGlobalState()->buildConceptUris( $skinTemplate ) );
	}

	/**
	 * Build concept uri array for nav urls
	 *
	 * @param SkinTemplate $skin
	 * @return string[][]
	 */
	private function buildConceptUris( SkinTemplate $skin ) {
		$title = $skin->getTitle();

		if ( $title === null ) {
			return [];
		}

		$entityId = $this->getValidEntityId( $title );

		if ( $entityId === null ) {
			return [];
		}

		return [
			'wb-concept-uri' => [
				'text' => $skin->msg( 'wikibase-concept-uri' ),
				'href' => $this->baseConceptUri . $entityId->getSerialization(),
				'title' => $skin->msg( 'wikibase-concept-uri-tooltip' )
			]
		];
	}

	/**
	 * Get a valid entity id, based on a series of checks
	 *
	 * @param Title $title
	 * @return EntityId|null
	 */
	private function getValidEntityId( Title $title ): ?EntityId {

		if ( !$this->nsLookup->isNamespaceWithEntities( $title->getNamespace() ) ) {
			return null;
		}

		$entityId = $this->idLookup->getEntityIdForTitle( $title );

		if ( $entityId === null ) {
			return null;
		}

		// As per T243779, a concept uri should be built for redirects, so the hasEntity check is skipped
		if ( $title->isRedirect() ) {
			return $entityId;
		}

		try {
			// Check if the entity exists
			// Placing in try catch block since there are cases where `hasEntity` throws an exception
			if ( !$this->entityLookup->hasEntity( $entityId ) ) {
				return null;
			}
		} catch ( EntityLookupException $error ) {
			$this->logger->warning( 'Could not lookup entity for id {id}: {exception}', [
				'id' => $entityId->getSerialization(),
				'exception' => $error->getMessage()
			] );

			return null;
		}

		return $entityId;
	}

}
