import EditFlow from '@/definitions/EditFlow';

export interface SelectedElement {
	link: HTMLAnchorElement;
	entityId: string;
	propertyId: string;
	entityTitle: string;
	editFlow: EditFlow;
}
