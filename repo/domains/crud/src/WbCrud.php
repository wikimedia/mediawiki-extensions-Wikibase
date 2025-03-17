<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud;

use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\EditMetadataRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemAliasesInLanguage\AddItemAliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyStatement\AddPropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateItem\CreateItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateProperty\CreateProperty;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItem\GetItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliases\GetItemAliases;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescription\GetItemDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptions\GetItemDescriptions;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptionWithFallback\GetItemDescriptionWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabel\GetItemLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabels\GetItemLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabelWithFallback\GetItemLabelWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestStatementSubjectRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetProperty\GetProperty;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliases\GetPropertyAliases;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescription\GetPropertyDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptions;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptionWithFallback\GetPropertyDescriptionWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabel\GetPropertyLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabels\GetPropertyLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabelWithFallback\GetPropertyLabelWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatement\GetPropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatements\GetPropertyStatements;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink\GetSitelink;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelinks\GetSitelinks;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem\PatchItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemAliases\PatchItemAliases;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemDescriptions\PatchItemDescriptions;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels\PatchItemLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty\PatchProperty;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyAliases\PatchPropertyAliases;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyDescriptions\PatchPropertyDescriptions;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyLabels\PatchPropertyLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyStatement\PatchPropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchSitelinks\PatchSitelinks;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemDescription\RemoveItemDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemLabel\RemoveItemLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyDescription\RemovePropertyDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyLabel\RemovePropertyLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyStatement\RemovePropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveSitelink\RemoveSitelink;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemDescription\SetItemDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyDescription\SetPropertyDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyLabel\SetPropertyLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink\SetSitelink;
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\DescriptionLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LabelLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementRemover;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementUpdater;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityRevisionLookupItemDataRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityRevisionLookupPropertyDataRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityRevisionLookupStatementRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdaterItemUpdater;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdaterPropertyUpdater;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\TermLookupEntityTermsRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\ValidatingRequestDeserializer;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\PreconditionMiddlewareFactory;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\StatementRedirectMiddlewareFactory;
use Wikibase\Repo\RestApi\Middleware\UnexpectedErrorHandlerMiddleware;

/**
 * @license GPL-2.0-or-later
 */
class WbCrud {

	public static function getGetItem( ?ContainerInterface $services = null ): GetItem {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetItem' );
	}

	public static function getCreateItem( ?ContainerInterface $services = null ): CreateItem {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.CreateItem' );
	}

	public static function getGetSitelinks( ?ContainerInterface $services = null ): GetSitelinks {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetSitelinks' );
	}

	public static function getGetSitelink( ?ContainerInterface $services = null ): GetSitelink {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetSitelink' );
	}

	public static function getGetItemLabels( ?ContainerInterface $services = null ): GetItemLabels {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetItemLabels' );
	}

	public static function getGetItemLabel( ?ContainerInterface $services = null ): GetItemLabel {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetItemLabel' );
	}

	public static function getGetItemLabelWithFallback( ?ContainerInterface $services = null ): GetItemLabelWithFallback {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetItemLabelWithFallback' );
	}

	public static function getGetItemDescriptions( ?ContainerInterface $services = null ): GetItemDescriptions {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetItemDescriptions' );
	}

	public static function getGetItemDescription( ?ContainerInterface $services = null ): GetItemDescription {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetItemDescription' );
	}

	public static function getGetItemDescriptionWithFallback( ?ContainerInterface $services = null ): GetItemDescriptionWithFallback {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetItemDescriptionWithFallback' );
	}

	public static function getGetItemAliases( ?ContainerInterface $services = null ): GetItemAliases {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetItemAliases' );
	}

	public static function getGetItemAliasesInLanguage( ?ContainerInterface $services = null ): GetItemAliasesInLanguage {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetItemAliasesInLanguage' );
	}

	public static function getSetItemLabel( ?ContainerInterface $services = null ): SetItemLabel {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.SetItemLabel' );
	}

	public static function getSetPropertyLabel( ?ContainerInterface $services = null ): SetPropertyLabel {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.SetPropertyLabel' );
	}

	public static function getSetItemDescription( ?ContainerInterface $services = null ): SetItemDescription {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.SetItemDescription' );
	}

	public static function getSetPropertyDescription( ?ContainerInterface $services = null ): SetPropertyDescription {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.SetPropertyDescription' );
	}

	public static function getGetItemStatements( ?ContainerInterface $services = null ): GetItemStatements {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetItemStatements' );
	}

	public static function getGetItemStatement( ?ContainerInterface $services = null ): GetItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbCrud.GetItemStatement' );
	}

	public static function getGetStatement( ?ContainerInterface $services = null ): GetStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetStatement' );
	}

	public static function getAddItemStatement( ?ContainerInterface $services = null ): AddItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.AddItemStatement' );
	}

	public static function getAddPropertyStatement( ?ContainerInterface $services = null ): AddPropertyStatement {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbCrud.AddPropertyStatement' );
	}

	public static function getReplaceItemStatement( ?ContainerInterface $services = null ): ReplaceItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.ReplaceItemStatement' );
	}

	public static function getReplacePropertyStatement( ?ContainerInterface $services = null ): ReplacePropertyStatement {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbCrud.ReplacePropertyStatement' );
	}

	public static function getReplaceStatement( ?ContainerInterface $services = null ): ReplaceStatement {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbCrud.ReplaceStatement' );
	}

	public static function getRemoveItemLabel( ?ContainerInterface $services = null ): RemoveItemLabel {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.RemoveItemLabel' );
	}

	public static function getRemovePropertyLabel( ?ContainerInterface $services = null ): RemovePropertyLabel {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.RemovePropertyLabel' );
	}

	public static function getRemoveItemDescription( ?ContainerInterface $services = null ): RemoveItemDescription {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.RemoveItemDescription' );
	}

	public static function getRemovePropertyDescription( ?ContainerInterface $services = null ): RemovePropertyDescription {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.RemovePropertyDescription' );
	}

	public static function getRemoveItemStatement( ?ContainerInterface $services = null ): RemoveItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.RemoveItemStatement' );
	}

	public static function getRemovePropertyStatement( ?ContainerInterface $services = null ): RemovePropertyStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.RemovePropertyStatement' );
	}

	public static function getRemoveStatement( ?ContainerInterface $services = null ): RemoveStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.RemoveStatement' );
	}

	public static function getPreconditionMiddlewareFactory( ?ContainerInterface $services = null ): PreconditionMiddlewareFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PreconditionMiddlewareFactory' );
	}

	public static function getPatchStatement( ?ContainerInterface $services = null ): PatchStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PatchStatement' );
	}

	public static function getPatchItemStatement( ?ContainerInterface $services = null ): PatchItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PatchItemStatement' );
	}

	public static function getPatchPropertyStatement( ?ContainerInterface $services = null ): PatchPropertyStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PatchPropertyStatement' );
	}

	public static function getPatchPropertyLabels( ?ContainerInterface $services = null ): PatchPropertyLabels {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PatchPropertyLabels' );
	}

	public static function getPatchPropertyDescriptions( ?ContainerInterface $services = null ): PatchPropertyDescriptions {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PatchPropertyDescriptions' );
	}

	public static function getEntityUpdater( ?ContainerInterface $services = null ): EntityUpdater {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbCrud.EntityUpdater' );
	}

	public static function getItemUpdater( ?ContainerInterface $services = null ): EntityUpdaterItemUpdater {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.ItemUpdater' );
	}

	public static function getPropertyUpdater( ?ContainerInterface $services = null ): EntityUpdaterPropertyUpdater {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PropertyUpdater' );
	}

	public static function getStatementUpdater( ?ContainerInterface $services = null ): StatementUpdater {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.StatementUpdater' );
	}

	public static function getStatementRemover( ?ContainerInterface $services = null ): StatementRemover {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.StatementRemover' );
	}

	public static function getItemDataRetriever( ?ContainerInterface $services = null ): EntityRevisionLookupItemDataRetriever {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.ItemDataRetriever' );
	}

	public static function getSitelinkDeserializer( ?ContainerInterface $services = null ): SitelinkDeserializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.SitelinkDeserializer' );
	}

	public static function getStatementRetriever( ?ContainerInterface $services = null ): EntityRevisionLookupStatementRetriever {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.StatementRetriever' );
	}

	public static function getStatementSerializer( ?ContainerInterface $services = null ): StatementSerializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.StatementSerializer' );
	}

	public static function getStatementDeserializer( ?ContainerInterface $services = null ): StatementDeserializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.StatementDeserializer' );
	}

	public static function getStatementRedirectMiddlewareFactory(
		?ContainerInterface $services = null
	): StatementRedirectMiddlewareFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.StatementRedirectMiddlewareFactory' );
	}

	public static function getUnexpectedErrorHandlerMiddleware( ?ContainerInterface $services = null ): UnexpectedErrorHandlerMiddleware {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.UnexpectedErrorHandlerMiddleware' );
	}

	public static function getPatchItem( ?ContainerInterface $services = null ): PatchItem {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PatchItem' );
	}

	public static function getPatchItemLabels( ?ContainerInterface $services = null ): PatchItemLabels {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PatchItemLabels' );
	}

	public static function getPatchItemDescriptions( ?ContainerInterface $services = null ): PatchItemDescriptions {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PatchItemDescriptions' );
	}

	public static function getPatchItemAliases( ?ContainerInterface $services = null ): PatchItemAliases {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PatchItemAliases' );
	}

	public static function getPatchProperty( ?ContainerInterface $services = null ): PatchProperty {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PatchProperty' );
	}

	public static function getPatchPropertyAliases( ?ContainerInterface $services = null ): PatchPropertyAliases {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PatchPropertyAliases' );
	}

	public static function getPatchSitelinks( ?ContainerInterface $services = null ): PatchSitelinks {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PatchSitelinks' );
	}

	public static function getAssertUserIsAuthorized( ?ContainerInterface $services = null ): AssertUserIsAuthorized {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.AssertUserIsAuthorized' );
	}

	public static function getGetLatestItemRevisionMetadata( ?ContainerInterface $services = null ): GetLatestItemRevisionMetadata {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetLatestItemRevisionMetadata' );
	}

	public static function getAssertItemExists( ?ContainerInterface $services = null ): AssertItemExists {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.AssertItemExists' );
	}

	public static function getAssertPropertyExists( ?ContainerInterface $services = null ): AssertPropertyExists {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.AssertPropertyExists' );
	}

	public static function getAssertStatementSubjectExists( ?ContainerInterface $services = null ): AssertStatementSubjectExists {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.AssertStatementSubjectExists' );
	}

	public static function getGetProperty( ?ContainerInterface $services = null ): GetProperty {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetProperty' );
	}

	public static function getCreateProperty( ?ContainerInterface $services = null ): CreateProperty {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.CreateProperty' );
	}

	public static function getPropertyDataRetriever( ?ContainerInterface $services = null ): EntityRevisionLookupPropertyDataRetriever {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.PropertyDataRetriever' );
	}

	public static function getGetLatestPropertyRevisionMetadata( ?ContainerInterface $services = null ): GetLatestPropertyRevisionMetadata {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetLatestPropertyRevisionMetadata' );
	}

	public static function getGetLatestStatementSubjectRevisionMetadata(
		?ContainerInterface $services = null
	): GetLatestStatementSubjectRevisionMetadata {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetLatestStatementSubjectRevisionMetadata' );
	}

	public static function getGetPropertyStatement( ?ContainerInterface $services = null ): GetPropertyStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetPropertyStatement' );
	}

	public static function getGetPropertyStatements( ?ContainerInterface $services = null ): GetPropertyStatements {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetPropertyStatements' );
	}

	public static function getGetPropertyLabel( ?ContainerInterface $services = null ): GetPropertyLabel {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetPropertyLabel' );
	}

	public static function getGetPropertyLabelWithFallback( ?ContainerInterface $services = null ): GetPropertyLabelWithFallback {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetPropertyLabelWithFallback' );
	}

	public static function getGetPropertyLabels( ?ContainerInterface $services = null ): GetPropertyLabels {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetPropertyLabels' );
	}

	public static function getGetPropertyDescription( ?ContainerInterface $services = null ): GetPropertyDescription {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetPropertyDescription' );
	}

	public static function getGetPropertyDescriptions( ?ContainerInterface $services = null ): GetPropertyDescriptions {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetPropertyDescriptions' );
	}

	public static function getGetPropertyDescriptionWithFallback(
		?ContainerInterface $services = null
	): GetPropertyDescriptionWithFallback {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetPropertyDescriptionWithFallback' );
	}

	public static function getGetPropertyAliasesInLanguage( ?ContainerInterface $services = null ): GetPropertyAliasesInLanguage {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetPropertyAliasesInLanguage' );
	}

	public static function getGetPropertyAliases( ?ContainerInterface $services = null ): GetPropertyAliases {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.GetPropertyAliases' );
	}

	public static function getValidatingRequestDeserializer( ?ContainerInterface $services = null ): ValidatingRequestDeserializer {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbCrud.ValidatingRequestDeserializer' );
	}

	public static function getTermLookupEntityTermsRetriever( ?ContainerInterface $services = null ): TermLookupEntityTermsRetriever {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbCrud.TermLookupEntityTermsRetriever' );
	}

	public static function getAddItemAliasesInLanguage( ?ContainerInterface $services = null ): AddItemAliasesInLanguage {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbCrud.AddItemAliasesInLanguage' );
	}

	public static function getAddPropertyAliasesInLanguage( ?ContainerInterface $services = null ): AddPropertyAliasesInLanguage {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbCrud.AddPropertyAliasesInLanguage' );
	}

	public static function getRemoveSitelink( ?ContainerInterface $services = null ): RemoveSitelink {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbCrud.RemoveSitelink' );
	}

	public static function getSetSitelink( ?ContainerInterface $services = null ): SetSitelink {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.SetSitelink' );
	}

	public static function getLabelLanguageCodeValidator( ?ContainerInterface $services = null ): LabelLanguageCodeValidator {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.LabelLanguageCodeValidator' );
	}

	public static function getDescriptionLanguageCodeValidator( ?ContainerInterface $services = null ): DescriptionLanguageCodeValidator {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.DescriptionLanguageCodeValidator' );
	}

	public static function getAliasLanguageCodeValidator( ?ContainerInterface $services = null ): AliasLanguageCodeValidator {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbCrud.AliasLanguageCodeValidator' );
	}

	public static function getEditMetadataRequestValidatingDeserializer(
		?ContainerInterface $services = null
	): EditMetadataRequestValidatingDeserializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( ValidatingRequestDeserializer::EDIT_METADATA_REQUEST_VALIDATING_DESERIALIZER );
	}

}
