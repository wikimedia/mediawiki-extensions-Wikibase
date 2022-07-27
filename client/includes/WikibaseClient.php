<?php

namespace Wikibase\Client;

use DataValues\Deserializers\DataValueDeserializer;
use ExternalUserNames;
use Language;
use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Serializers\Serializer;
use Site;
use Wikibase\Client\Changes\AffectedPagesFinder;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\ParserFunctions\Runner;
use Wikibase\Client\DataAccess\ParserFunctions\StatementGroupRendererFactory;
use Wikibase\Client\DataAccess\ReferenceFormatterFactory;
use Wikibase\Client\Hooks\LangLinkHandlerFactory;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\Hooks\WikibaseClientHookRunner;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\DataAccess\AliasTermBuffer;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\EntitySourceAndTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\Formatters\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\Formatters\WikibaseValueFormatterBuilders;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\MessageInLanguageProvider;
use Wikibase\Lib\Rdbms\ClientDomainDbFactory;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\PropertyOrderProvider;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Lib\Store\Sql\Terms\TermInLangIdsResolverFactory;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermFallbackCacheFactory;
use Wikibase\Lib\WikibaseContentLanguages;

/**
 * Top level factory for the WikibaseClient extension.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
final class WikibaseClient {

	private function __construct() {
		// should not be instantiated
	}

	/**
	 * Returns a low level factory object for creating formatters for well known data types.
	 *
	 * @warning This is for use with bootstrap code in WikibaseClient.datatypes.php only!
	 * Program logic should use WikibaseClient::getSnakFormatterFactory() instead!
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	public static function getDefaultValueFormatterBuilders( ContainerInterface $services = null ): WikibaseValueFormatterBuilders {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.DefaultValueFormatterBuilders' );
	}

	public static function getKartographerEmbeddingHandler(
		ContainerInterface $services = null
	): ?CachingKartographerEmbeddingHandler {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.KartographerEmbeddingHandler' );
	}

	/**
	 * @warning This is for use with bootstrap code in WikibaseClient.datatypes.php only!
	 * Program logic should use {@link WikibaseClient::getSnakFormatterFactory()} instead!
	 */
	public static function getDefaultSnakFormatterBuilders( ContainerInterface $services = null ): WikibaseSnakFormatterBuilders {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.DefaultSnakFormatterBuilders' );
	}

	public static function getDataTypeDefinitions( ContainerInterface $services = null ): DataTypeDefinitions {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.DataTypeDefinitions' );
	}

	public static function getEntitySourceDefinitions( ContainerInterface $services = null ): EntitySourceDefinitions {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.EntitySourceDefinitions' );
	}

	public static function getEntityTypeDefinitions( ContainerInterface $services = null ): EntityTypeDefinitions {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.EntityTypeDefinitions' );
	}

	public static function getDataTypeFactory( ContainerInterface $services = null ): DataTypeFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.DataTypeFactory' );
	}

	public static function getEntityIdParser( ContainerInterface $services = null ): EntityIdParser {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.EntityIdParser' );
	}

	public static function getEntityIdComposer( ContainerInterface $services = null ): EntityIdComposer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.EntityIdComposer' );
	}

	public static function getWikibaseServices( ContainerInterface $services = null ): WikibaseServices {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.WikibaseServices' );
	}

	public static function getDataAccessSettings( ContainerInterface $services = null ): DataAccessSettings {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.DataAccessSettings' );
	}

	public static function getEntityLookup( ContainerInterface $services = null ): EntityLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.EntityLookup' );
	}

	public static function getEntityRevisionLookup( ContainerInterface $services = null ): EntityRevisionLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.EntityRevisionLookup' );
	}

	public static function getTermBuffer( ContainerInterface $services = null ): TermBuffer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.TermBuffer' );
	}

	public static function getAliasTermBuffer( ContainerInterface $services = null ): AliasTermBuffer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.AliasTermBuffer' );
	}

	public static function getTermLookup( ContainerInterface $services = null ): TermLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.TermLookup' );
	}

	public static function getPrefetchingTermLookup( ContainerInterface $services = null ): PrefetchingTermLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.PrefetchingTermLookup' );
	}

	public static function getPropertyDataTypeLookup( ContainerInterface $services = null ): PropertyDataTypeLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.PropertyDataTypeLookup' );
	}

	public static function getStringNormalizer( ContainerInterface $services = null ): StringNormalizer {
		return ( $services ?: MediawikiServices::getInstance() )
				->get( 'WikibaseClient.StringNormalizer' );
	}

	public static function getRepoLinker( ContainerInterface $services = null ): RepoLinker {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.RepoLinker' );
	}

	public static function getLanguageFallbackChainFactory( ContainerInterface $services = null ): LanguageFallbackChainFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.LanguageFallbackChainFactory' );
	}

	public static function getStore( ContainerInterface $services = null ): ClientStore {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.Store' );
	}

	/**
	 * @deprecated
	 */
	public static function getUserLanguage( ContainerInterface $services = null ): Language {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.UserLanguage' );
	}

	public static function getSettings( ContainerInterface $services = null ): SettingsArray {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.Settings' );
	}

	public static function getLogger( ContainerInterface $services = null ): LoggerInterface {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.Logger' );
	}

	/**
	 * Returns the this client wiki's site object.
	 *
	 * This is taken from the siteGlobalID setting, which defaults
	 * to the wiki's database name.
	 *
	 * If the configured site ID is not found in the sites table, a
	 * new Site object is constructed from the configured ID.
	 */
	public static function getSite( ContainerInterface $services = null ): Site {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.Site' );
	}

	/**
	 * Returns the site group ID for the group to be used for language links.
	 * This is typically the group the client wiki itself belongs to, but
	 * can be configured to be otherwise using the languageLinkSiteGroup setting.
	 */
	public static function getLangLinkSiteGroup( ContainerInterface $services = null ): string {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.LangLinkSiteGroup' );
	}

	/**
	 * Returns the site group IDs for the group to be used for language links.
	 * This is typically the group the client wiki itself belongs to, but
	 * can be configured to be otherwise using the languageLinkSiteGroup setting.
	 * It can also be configured to be more than one group.
	 */
	public static function getLangLinkSiteGroups( ContainerInterface $services = null ): array {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.LangLinkSiteGroups' );
	}

	/**
	 * Get site group ID
	 */
	public static function getSiteGroup( ContainerInterface $services = null ): string {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.SiteGroup' );
	}

	/**
	 * Returns a OutputFormatSnakFormatterFactory the provides SnakFormatters
	 * for different output formats.
	 */
	public static function getSnakFormatterFactory( ContainerInterface $services = null ): OutputFormatSnakFormatterFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.SnakFormatterFactory' );
	}

	/**
	 * Returns a OutputFormatValueFormatterFactory the provides ValueFormatters
	 * for different output formats.
	 */
	public static function getValueFormatterFactory( ContainerInterface $services = null ): OutputFormatValueFormatterFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.ValueFormatterFactory' );
	}

	public static function getRepoItemUriParser( ContainerInterface $services = null ): EntityIdParser {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.RepoItemUriParser' );
	}

	public static function getNamespaceChecker( ContainerInterface $services = null ): NamespaceChecker {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.NamespaceChecker' );
	}

	public static function getLangLinkHandlerFactory( ContainerInterface $services = null ): LangLinkHandlerFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.LangLinkHandlerFactory' );
	}

	public static function getParserOutputDataUpdater( ContainerInterface $services = null ): ClientParserOutputDataUpdater {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.ParserOutputDataUpdater' );
	}

	public static function getSidebarLinkBadgeDisplay( ContainerInterface $service = null ): SidebarLinkBadgeDisplay {
		return ( $service ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.SidebarLinkBadgeDisplay' );
	}

	public static function getLanguageLinkBadgeDisplay( ContainerInterface $services = null ): LanguageLinkBadgeDisplay {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.LanguageLinkBadgeDisplay' );
	}

	public static function getBaseDataModelDeserializerFactory(
		ContainerInterface $services = null
	): DeserializerFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.BaseDataModelDeserializerFactory' );
	}

	/**
	 * Returns a SerializerFactory creating serializers that generate the most compact serialization.
	 * A factory returned has knowledge about items, properties, and the elements they are made of,
	 * but no other entity types.
	 */
	public static function getCompactBaseDataModelSerializerFactory( ContainerInterface $services = null ): SerializerFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.CompactBaseDataModelSerializerFactory' );
	}

	/**
	 * Returns an entity serializer that generates the most compact serialization.
	 */
	public static function getCompactEntitySerializer( ContainerInterface $services = null ): Serializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.CompactEntitySerializer' );
	}

	public static function getDataValueDeserializer( ContainerInterface $services = null ): DataValueDeserializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.DataValueDeserializer' );
	}

	public static function getOtherProjectsSidebarGeneratorFactory(
		ContainerInterface $services = null
	): OtherProjectsSidebarGeneratorFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.OtherProjectsSidebarGeneratorFactory' );
	}

	public static function getEntityChangeFactory( ContainerInterface $services = null ): EntityChangeFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.EntityChangeFactory' );
	}

	public static function getEntityChangeLookup( ContainerInterface $services = null ): EntityChangeLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.EntityChangeLookup' );
	}

	public static function getEntityDiffer( ContainerInterface $services = null ): EntityDiffer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.EntityDiffer' );
	}

	public static function getStatementGroupRendererFactory( ContainerInterface $services = null ): StatementGroupRendererFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.StatementGroupRendererFactory' );
	}

	public static function getDataAccessSnakFormatterFactory( ContainerInterface $services = null ): DataAccessSnakFormatterFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.DataAccessSnakFormatterFactory' );
	}

	public static function getPropertyParserFunctionRunner( ContainerInterface $services = null ): Runner {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.PropertyParserFunctionRunner' );
	}

	public static function getOtherProjectsSitesProvider( ContainerInterface $services = null ): OtherProjectsSitesProvider {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.OtherProjectsSitesProvider' );
	}

	public static function getAffectedPagesFinder( ContainerInterface $services = null ): AffectedPagesFinder {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.AffectedPagesFinder' );
	}

	public static function getChangeHandler( ContainerInterface $services = null ): ChangeHandler {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.ChangeHandler' );
	}

	public static function getRecentChangeFactory( ContainerInterface $services = null ): RecentChangeFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.RecentChangeFactory' );
	}

	/**
	 * Returns an {@link ExternalUserNames} that can be used to link to the
	 * {@link getItemAndPropertySource item and property source},
	 * if an interwiki prefix for that source (and its site) is known.
	 */
	public static function getExternalUserNames( ContainerInterface $services = null ): ?ExternalUserNames {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.ExternalUserNames' );
	}

	public static function getItemAndPropertySource( ContainerInterface $services = null ): DatabaseEntitySource {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.ItemAndPropertySource' );
	}

	public static function getWikibaseContentLanguages( ContainerInterface $services = null ): WikibaseContentLanguages {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.WikibaseContentLanguages' );
	}

	/**
	 * Get a ContentLanguages object holding the languages available for labels, descriptions and aliases.
	 */
	public static function getTermsLanguages( ContainerInterface $services = null ): ContentLanguages {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.TermsLanguages' );
	}

	public static function getRestrictedEntityLookup( ContainerInterface $services = null ): RestrictedEntityLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.RestrictedEntityLookup' );
	}

	public static function getPropertyOrderProvider( ContainerInterface $services = null ): PropertyOrderProvider {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.PropertyOrderProvider' );
	}

	public static function getEntityNamespaceLookup( ContainerInterface $services = null ): EntityNamespaceLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.EntityNamespaceLookup' );
	}

	public static function getTermFallbackCache( ContainerInterface $services = null ): TermFallbackCacheFacade {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.TermFallbackCache' );
	}

	public static function getTermFallbackCacheFactory( ContainerInterface $services = null ): TermFallbackCacheFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.TermFallbackCacheFactory' );
	}

	public static function getEntityIdLookup( ContainerInterface $services = null ): EntityIdLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.EntityIdLookup' );
	}

	public static function getDescriptionLookup( ContainerInterface $services = null ): DescriptionLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.DescriptionLookup' );
	}

	public static function getPropertyLabelResolver( ContainerInterface $services = null ): PropertyLabelResolver {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.PropertyLabelResolver' );
	}

	public static function getReferenceFormatterFactory( ContainerInterface $services = null ): ReferenceFormatterFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.ReferenceFormatterFactory' );
	}

	public static function getItemSource( ContainerInterface $services = null ): DatabaseEntitySource {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.ItemSource' );
	}

	public static function getPropertySource( ContainerInterface $services = null ): DatabaseEntitySource {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.PropertySource' );
	}

	public static function getTermInLangIdsResolverFactory(
		ContainerInterface $services = null
	): TermInLangIdsResolverFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.TermInLangIdsResolverFactory' );
	}

	public static function getMessageInLanguageProvider( ContainerInterface $services = null ): MessageInLanguageProvider {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.MessageInLanguageProvider' );
	}

	public static function getClientDomainDbFactory( ContainerInterface $services = null ): ClientDomainDbFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.ClientDomainDbFactory' );
	}

	public static function getRepoDomainDbFactory( ContainerInterface $services = null ): RepoDomainDbFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.RepoDomainDbFactory' );
	}

	public static function getEntitySourceAndTypeDefinitions( ContainerInterface $services = null ): EntitySourceAndTypeDefinitions {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.EntitySourceAndTypeDefinitions' );
	}

	public static function getUsageAccumulatorFactory( ContainerInterface $services = null ): UsageAccumulatorFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.UsageAccumulatorFactory' );
	}

	public static function getHookRunner( ContainerInterface $services = null ): WikibaseClientHookRunner {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.HookRunner' );
	}

	public static function getRedirectResolvingLatestRevisionLookup(
		ContainerInterface $services = null
	): RedirectResolvingLatestRevisionLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.RedirectResolvingLatestRevisionLookup' );
	}

	public static function getFallbackLabelDescriptionLookupFactory(
		ContainerInterface $services = null
	): FallbackLabelDescriptionLookupFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseClient.FallbackLabelDescriptionLookupFactory' );
	}

}
