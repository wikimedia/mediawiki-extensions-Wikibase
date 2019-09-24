import EditFlow from '@/definitions/EditFlow';
import EntityId from '@/datamodel/EntityId';

export default interface AppInformation {
	/** The entity ID to edit. */
	entityId: EntityId;
	/** The property ID of the statement(s) to edit. */
	propertyId: EntityId;
	/** The edit flow to use for editing. */
	editFlow: EditFlow;
}
