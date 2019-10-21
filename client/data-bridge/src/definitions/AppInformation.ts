import EditFlow from '@/definitions/EditFlow';
import EntityId from '@/datamodel/EntityId';
import WikibaseClientConfiguration from '@/definitions/WikibaseClientConfiguration';

export default interface AppInformation {
	/** The entity ID to edit. */
	entityId: EntityId;
	/** The property ID of the statement(s) to edit. */
	propertyId: EntityId;
	/** The edit flow to use for editing. */
	editFlow: EditFlow;
	/** The client configuration of Wikibase */
	client: WikibaseClientConfiguration;
}
