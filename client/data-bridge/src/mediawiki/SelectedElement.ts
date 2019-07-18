import EditFlow from '@/definitions/EditFlow';

export interface SelectedElement {
	link: HTMLAnchorElement;
	entityID: string;
	propertyID: string;
	editFlow: EditFlow;
}
