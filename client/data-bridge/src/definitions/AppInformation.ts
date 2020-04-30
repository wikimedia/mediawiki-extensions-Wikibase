import EditFlow from '@/definitions/EditFlow';
import EntityId from '@/datamodel/EntityId';
import WikibaseClientConfiguration from '@/definitions/WikibaseClientConfiguration';

export default interface AppInformation {
	/** The page title (including namespace, if any) of the client page using the data. */
	pageTitle: string;
	/** The entity ID to edit. */
	entityId: EntityId;
	/** The property ID of the statement(s) to edit. */
	propertyId: EntityId;
	/** The page title (including namespace, if any) of the entity ID to edit. */
	entityTitle: string;
	/** The edit flow to use for editing. */
	editFlow: EditFlow;
	/** The client configuration of Wikibase */
	client: WikibaseClientConfiguration;
	/** The href of the original link that is overloaded by bridge */
	originalHref: string;
	/** The exact URL of the client page on which the Bridge was opened. */
	pageUrl: string;
	/** The user name of the user currently logged into MediaWiki. */
	userName: string|null;
}
