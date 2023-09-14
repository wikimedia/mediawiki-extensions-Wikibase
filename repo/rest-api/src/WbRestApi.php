<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi;

use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;
use Wikibase\Repo\RestApi\Application\Serialization\SerializerFactory;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliases;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\GetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabels;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestStatementSubjectRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetProperty;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabels;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatements;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabels;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\StatementRemover;
use Wikibase\Repo\RestApi\Domain\Services\StatementUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupItemDataRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupPropertyDataRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupStatementRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\PreconditionMiddlewareFactory;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UnexpectedErrorHandlerMiddleware;

/**
 * @license GPL-2.0-or-later
 */
class WbRestApi {

	public static function getGetItem( ContainerInterface $services = null ): GetItem {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItem' );
	}

	public static function getGetItemAliases( ContainerInterface $services = null ): GetItemAliases {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemAliases' );
	}

	public static function getGetItemAliasesInLanguage( ContainerInterface $services = null ): GetItemAliasesInLanguage {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemAliasesInLanguage' );
	}

	public static function getGetItemDescription( ContainerInterface $services = null ): GetItemDescription {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemDescription' );
	}

	public static function getGetItemDescriptions( ContainerInterface $services = null ): GetItemDescriptions {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemDescriptions' );
	}

	public static function getSetItemDescription( ContainerInterface $services = null ): SetItemDescription {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.SetItemDescription' );
	}

	public static function getGetItemLabel( ContainerInterface $services = null ): GetItemLabel {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemLabel' );
	}

	public static function getGetItemLabels( ContainerInterface $services = null ): GetItemLabels {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemLabels' );
	}

	public static function getSetItemLabel( ContainerInterface $services = null ): SetItemLabel {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.SetItemLabel' );
	}

	public static function getGetItemStatements( ContainerInterface $services = null ): GetItemStatements {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemStatements' );
	}

	public static function getGetItemStatement( ContainerInterface $services = null ): GetItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbRestApi.GetItemStatement' );
	}

	public static function getGetStatement( ContainerInterface $services = null ): GetStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetStatement' );
	}

	public static function getSerializerFactory( ContainerInterface $services = null ): SerializerFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.SerializerFactory' );
	}

	public static function getAddItemStatement( ContainerInterface $services = null ): AddItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.AddItemStatement' );
	}

	public static function getAddPropertyStatement( ContainerInterface $services = null ): AddPropertyStatement {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbRestApi.AddPropertyStatement' );
	}

	public static function getReplaceItemStatement( ContainerInterface $services = null ): ReplaceItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.ReplaceItemStatement' );
	}

	public static function getReplacePropertyStatement( ContainerInterface $services = null ): ReplacePropertyStatement {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbRestApi.ReplacePropertyStatement' );
	}

	public static function getReplaceStatement( ContainerInterface $services = null ): ReplaceStatement {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbRestApi.ReplaceStatement' );
	}

	public static function getRemoveItemStatement( ContainerInterface $services = null ): RemoveItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.RemoveItemStatement' );
	}

	public static function getRemoveStatement( ContainerInterface $services = null ): RemoveStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.RemoveStatement' );
	}

	public static function getPreconditionMiddlewareFactory( ContainerInterface $services = null ): PreconditionMiddlewareFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.PreconditionMiddlewareFactory' );
	}

	public static function getPatchStatement( ContainerInterface $services = null ): PatchStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.PatchStatement' );
	}

	public static function getPatchItemStatement( ContainerInterface $services = null ): PatchItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.PatchItemStatement' );
	}

	public static function getPatchPropertyStatement( ContainerInterface $services = null ): PatchPropertyStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.PatchPropertyStatement' );
	}

	public static function getEntityUpdater( ContainerInterface $services = null ): EntityUpdater {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbRestApi.EntityUpdater' );
	}

	public static function getItemUpdater( ContainerInterface $services = null ): ItemUpdater {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.ItemUpdater' );
	}

	public static function getStatementUpdater( ContainerInterface $services = null ): StatementUpdater {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.StatementUpdater' );
	}

	public static function getStatementRemover( ContainerInterface $services = null ): StatementRemover {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.StatementRemover' );
	}

	public static function getItemDataRetriever( ContainerInterface $services = null ): EntityRevisionLookupItemDataRetriever {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.ItemDataRetriever' );
	}

	public static function getStatementRetriever( ContainerInterface $services = null ): EntityRevisionLookupStatementRetriever {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.StatementRetriever' );
	}

	public static function getStatementDeserializer( ContainerInterface $services = null ): StatementDeserializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.StatementDeserializer' );
	}

	public static function getUnexpectedErrorHandlerMiddleware( ContainerInterface $services = null ): UnexpectedErrorHandlerMiddleware {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.UnexpectedErrorHandlerMiddleware' );
	}

	public static function getPatchItemLabels( ContainerInterface $services = null ): PatchItemLabels {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.PatchItemLabels' );
	}

	public static function getAssertUserIsAuthorized( ContainerInterface $services = null ): AssertUserIsAuthorized {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.AssertUserIsAuthorized' );
	}

	public static function getGetLatestItemRevisionMetadata( ContainerInterface $services = null ): GetLatestItemRevisionMetadata {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetLatestItemRevisionMetadata' );
	}

	public static function getAssertItemExists( ContainerInterface $services = null ): AssertItemExists {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.AssertItemExists' );
	}

	public static function getAssertPropertyExists( ContainerInterface $services = null ): AssertPropertyExists {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.AssertPropertyExists' );
	}

	public static function getAssertStatementSubjectExists( ContainerInterface $services = null ): AssertStatementSubjectExists {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.AssertStatementSubjectExists' );
	}

	public static function getGetProperty( ContainerInterface $services = null ): GetProperty {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetProperty' );
	}

	public static function getPropertyDataRetriever( ContainerInterface $services = null ): EntityRevisionLookupPropertyDataRetriever {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.PropertyDataRetriever' );
	}

	public static function getGetLatestPropertyRevisionMetadata( ContainerInterface $services = null ): GetLatestPropertyRevisionMetadata {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetLatestPropertyRevisionMetadata' );
	}

	public static function getGetLatestStatementSubjectRevisionMetadata(
		ContainerInterface $services = null
	): GetLatestStatementSubjectRevisionMetadata {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetLatestStatementSubjectRevisionMetadata' );
	}

	public static function getGetPropertyStatement( ContainerInterface $services = null ): GetPropertyStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetPropertyStatement' );
	}

	public static function getGetPropertyStatements( ContainerInterface $services = null ): GetPropertyStatements {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetPropertyStatements' );
	}

	public static function getGetPropertyLabels( ContainerInterface $services = null ): GetPropertyLabels {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetPropertyLabels' );
	}

	public static function getEditMetadataValidator( ContainerInterface $services = null ): EditMetadataValidator {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbRestApi.EditMetadataValidator' );
	}

	public static function getValidatingRequestDeserializer( ContainerInterface $services = null ): ValidatingRequestDeserializer {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbRestApi.ValidatingRequestDeserializer' );
	}

}
