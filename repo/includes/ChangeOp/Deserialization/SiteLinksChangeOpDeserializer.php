<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\SiteLinkChangeOpFactory;
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
	 * @var SiteLinkBadgeLookup
	 */
	private $badgeLookup;

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
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @param SiteLinkChangeOpSerializationValidator $siteLinkChangeOpSerializationValidator
	 * @param SiteLinkBadgeLookup $badgeLookup
	 * @param SiteLinkChangeOpFactory $siteLinkChangeOpFactory
	 * @param SiteLinkTargetProvider $siteLinkTargetProvider
	 * @param StringNormalizer $stringNormalizer
	 * @param string[] $siteLinkGroups
	 */
	public function __construct(
		SiteLinkChangeOpSerializationValidator $siteLinkChangeOpSerializationValidator,
		SiteLinkBadgeLookup $badgeLookup,
		SiteLinkChangeOpFactory $siteLinkChangeOpFactory,
		SiteLinkTargetProvider $siteLinkTargetProvider,
		StringNormalizer $stringNormalizer,
		array $siteLinkGroups
	) {
		$this->siteLinkChangeOpSerializationValidator = $siteLinkChangeOpSerializationValidator;
		$this->badgeLookup = $badgeLookup;
		$this->siteLinkChangeOpFactory = $siteLinkChangeOpFactory;
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
		$this->stringNormalizer = $stringNormalizer;
		$this->siteLinkGroups = $siteLinkGroups;
	}

	/**
	 * NOTE: this is a trickier one since it is very intermingled with EditEntity/ModifyEntity and
	 *       it needs to know about the Item
	 *
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
			$this->siteLinkChangeOpSerializationValidator->checkSiteLinks( $serialization, $siteId, $sites );
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
					? $this->badgeLookup->parseSiteLinkBadgesSerialization( $serialization['badges'] )
					: null;

				if ( isset( $serialization['title'] ) ) {
					$linkPage = $linkSite->normalizePageName( $this->stringNormalizer->trimWhitespace( $serialization['title'] ) );

					if ( $linkPage === false ) {
						// TODO: in this API class is supposed to print i18n-ed version of 'no-external-page' message,
						// using $globalSiteId and $serialization['title'] as arguments
						throw new ChangeOpDeserializationException(
							'A page "' . $serialization['title'] . '" could not be found on "' . $globalSiteId . '"',
							'no-external-page',
							$globalSiteId,
							$serialization['title']
						);
					}
				} else {
					$linkPage = null;

					// TODO: in this API class is supposed to print i18n-ed version of 'no-such-sitelink' message,
					// using $globalSiteId as an argument
					/*if ( !$item->getSiteLinkList()->hasLinkWithSiteId( $globalSiteId ) ) {
						$this->errorReporter->dieMessage( 'no-such-sitelink', $globalSiteId );
					}*/
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

}
