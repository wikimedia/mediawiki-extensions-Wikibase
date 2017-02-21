<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\StringNormalizer;

/**
 * Deserializer for site link change requests.
 *
 * @see docs/change-op-serialization.wiki for documentation on site link change request format.
 *
 * @license GPL-2.0+
 */
class SiteLinksChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @var SiteLinkChangeOpSerializationValidator
	 */
	private $siteLinkChangeOpSerializationValidator;

	/**
	 * @var SiteLinkChangeOpFactory
	 */
	private $siteLinkChangeOpFactory;

	/**
	 * @var SiteLinkTargetProvider
	 */
	private $siteLinkTargetProvider;

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @param SiteLinkChangeOpSerializationValidator $siteLinkChangeOpSerializationValidator
	 * @param SiteLinkChangeOpFactory $siteLinkChangeOpFactory
	 * @param SiteLinkTargetProvider $siteLinkTargetProvider
	 * @param EntityIdParser $entityIdParser
	 * @param StringNormalizer $stringNormalizer
	 * @param string[] $siteLinkGroups
	 */
	public function __construct(
		SiteLinkChangeOpSerializationValidator $siteLinkChangeOpSerializationValidator,
		SiteLinkChangeOpFactory $siteLinkChangeOpFactory,
		SiteLinkTargetProvider $siteLinkTargetProvider,
		EntityIdParser $entityIdParser,
		StringNormalizer $stringNormalizer,
		array $siteLinkGroups
	) {
		$this->siteLinkChangeOpSerializationValidator = $siteLinkChangeOpSerializationValidator;
		$this->siteLinkChangeOpFactory = $siteLinkChangeOpFactory;
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
		$this->entityIdParser = $entityIdParser;
		$this->stringNormalizer = $stringNormalizer;
		$this->siteLinkGroups = $siteLinkGroups;
	}

	/**
	 * @see ChangeOpDeserializer::createEntityChangeOp
	 *
	 * @param array[] $changeRequest
	 *
	 * @return ChangeOp
	 *
	 * @throws ChangeOpDeserializationException
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		$this->assertIsArray( $changeRequest['sitelinks'] );

		$siteLinksChangeOps = new ChangeOps();
		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );

		foreach ( $changeRequest['sitelinks'] as $siteId => $serialization ) {
			$this->siteLinkChangeOpSerializationValidator->validateSiteLinkSerialization( $serialization, $siteId, $sites );
			$globalSiteId = $serialization['site'];

			if ( !$sites->hasSite( $globalSiteId ) ) {
				throw new ChangeOpDeserializationException( "There is no site for global site id '$globalSiteId'", 'no-such-site' );
			}

			$linkSite = $sites->getSite( $globalSiteId );
			$shouldRemove = array_key_exists( 'remove', $serialization )
				|| ( !isset( $serialization['title'] ) && !isset( $serialization['badges'] ) )
				|| ( isset( $serialization['title'] ) && $serialization['title'] === '' );

			if ( $shouldRemove ) {
				$siteLinksChangeOps->add( $this->siteLinkChangeOpFactory->newRemoveSiteLinkOp( $globalSiteId ) );
			} else {
				$badges = ( isset( $serialization['badges'] ) )
					? $this->getBadgeItemIds( $serialization['badges'] )
					: null;

				if ( isset( $serialization['title'] ) ) {
					$linkPage = $linkSite->normalizePageName( $this->stringNormalizer->trimWhitespace( $serialization['title'] ) );

					if ( $linkPage === false ) {
						// FIXME: in this case API class is supposed to print i18n-ed version of 'no-external-page' message,
						// using $globalSiteId and $serialization['title'] as arguments. How this should be achieved
						// now with Api\EditEntity using deserializers throwing ChangeOpDeserializationExceptions?
						throw new ChangeOpDeserializationException(
							'A page "' . $serialization['title'] . '" could not be found on "' . $globalSiteId . '"',
							'no-external-page',
							$globalSiteId,
							$serialization['title']
						);
					}
				} else {
					$linkPage = null;
				}

				$siteLinksChangeOps->add( $this->siteLinkChangeOpFactory->newSetSiteLinkOp( $globalSiteId, $linkPage, $badges ) );
			}
		}

		return $siteLinksChangeOps;
	}

	/**
	 * @param array $sitelinks
	 *
	 * @throws ChangeOpDeserializationException
	 */
	private function assertIsArray( $sitelinks ) {
		if ( !is_array( $sitelinks ) ) {
			throw new ChangeOpDeserializationException( 'List of sitelinks must be an array', 'not-recognized-array' );
		}
	}

	/**
	 * @param string[] $badgeSerialization
	 *
	 * @return ItemId[]
	 */
	private function getBadgeItemIds( array $badgeSerialization ) {
		$badgeIds = [];

		foreach ( $badgeSerialization as $badge ) {
			$badgeIds[] = $this->entityIdParser->parse( $badge );
		}

		return $badgeIds;
	}

}
