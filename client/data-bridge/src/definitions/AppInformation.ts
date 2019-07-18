import EditFlow from '@/definitions/EditFlow';

export default interface AppInformation {
	/** The entity ID to edit. */
	entityId: string;
	/** The property ID of the statement(s) to edit. */
	propertyId: string;
	/** The edit flow to use for editing. */
	editFlow: EditFlow;
}
