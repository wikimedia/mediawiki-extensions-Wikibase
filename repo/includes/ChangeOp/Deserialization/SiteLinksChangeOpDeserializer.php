<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use SiteList;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\SiteLinkChangeOpFactory;
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
	 * @var SiteLinkBadgeChangeOpSerializationValidator
	 */
	private $badgeChangeOpSerializationValidator;

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
	 * @param SiteLinkBadgeChangeOpSerializationValidator $badgeChangeOpSerializationValidator
	 * @param SiteLinkChangeOpFactory $siteLinkChangeOpFactory
	 * @param SiteLinkTargetProvider $siteLinkTargetProvider
	 * @param EntityIdParser $entityIdParser
	 * @param StringNormalizer $stringNormalizer
	 * @param string[] $siteLinkGroups
	 */
	public function __construct(
		SiteLinkBadgeChangeOpSerializationValidator $badgeChangeOpSerializationValidator,
		SiteLinkChangeOpFactory $siteLinkChangeOpFactory,
		SiteLinkTargetProvider $siteLinkTargetProvider,
		EntityIdParser $entityIdParser,
		StringNormalizer $stringNormalizer,
		array $siteLinkGroups
	) {
		$this->badgeChangeOpSerializationValidator = $badgeChangeOpSerializationValidator;
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
		$this->assertArray( $changeRequest['sitelinks'], 'List of sitelinks must be an array' );

		$siteLinksChangeOps = new ChangeOps();
		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );

		foreach ( $changeRequest['sitelinks'] as $siteId => $serialization ) {
			$this->validateSiteLinkSerialization( $serialization, $siteId, $sites );
			$globalSiteId = $serialization['site'];

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
						throw new ChangeOpDeserializationException(
							'A page "' . $serialization['title'] . '" could not be found on "' . $globalSiteId . '"',
							'no-external-page',
							[ $globalSiteId,  $serialization['title'] ]
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
	 * @param array $serialization Site link serialization array
	 * @param string $siteCode
	 * @param SiteList|null $sites Valid sites. Null for skipping site validity check.
	 *
	 * @throws ChangeOpDeserializationException
	 */
	private function validateSiteLinkSerialization( $serialization, $siteCode, SiteList $sites = null ) {
		$this->assertArray( $serialization, 'An array was expected, but not found' );

		if ( !array_key_exists( 'site', $serialization ) ) {
			throw new ChangeOpDeserializationException( 'Site must be provided', 'no-site' );
		}
		$this->assertString( $serialization['site'], 'A string was expected, but not found' );

		if ( !is_numeric( $siteCode ) ) {
			if ( $siteCode !== $serialization['site'] ) {
				throw new ChangeOpDeserializationException(
					"inconsistent site: $siteCode is not equal to {$serialization['site']}",
					'inconsistent-site'
				);
			}
		}

		if ( $sites !== null && !$sites->hasSite( $serialization['site'] ) ) {
			throw new ChangeOpDeserializationException( 'Unknown site: ' . $serialization['site'], 'not-recognized-site' );
		}

		if ( isset( $serialization['title'] ) ) {
			$this->assertString( $serialization['title'], 'A string was expected, but not found' );
		}

		if ( isset( $serialization['badges'] ) ) {
			$this->assertArray( $serialization['badges'], 'Badges: an array was expected, but not found' );
			$this->badgeChangeOpSerializationValidator->validateBadgeSerialization( $serialization['badges'] );
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

	/**
	 * @param string $message
	 * @param string $errorCode
	 *
	 * @throws ChangeOpDeserializationException
	 */
	private function throwException( $message, $errorCode ) {
		throw new ChangeOpDeserializationException( $message, $errorCode );
	}

	/**
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertArray( $value, $message ) {
		$this->assertType( 'array', $value, $message );
	}

	/**
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertString( $value, $message ) {
		$this->assertType( 'string', $value, $message );
	}

	/**
	 * @param string $type
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertType( $type, $value, $message ) {
		if ( gettype( $value ) !== $type ) {
			$this->throwException( $message, 'not-recognized-' . $type );
		}
	}

}
