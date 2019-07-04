import EditFlow from '@/datamodel/EditFlow';

export interface SelectedElement {
	link: HTMLAnchorElement;
	entityID: string;
	propertyID: string;
	editFlow: EditFlow;
}
