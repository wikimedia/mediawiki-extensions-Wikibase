import MwWindow from '@/@types/mediawiki/MwWindow';
import AppBridge from '@/definitions/AppBridge';
import prepareContainer from '@/mediawiki/prepareContainer';
import createServices from '@/mediawiki/createServices';
import { SelectedElement } from '@/mediawiki/SelectedElement';

export default class Dispatcher {
	public static readonly APP_DOM_CONTAINER_ID = 'data-bridge-container';

	private readonly mwWindow: MwWindow;
	private readonly app: AppBridge;

	public constructor( mwWindow: MwWindow, app: AppBridge ) {
		this.mwWindow = mwWindow;
		this.app = app;
	}

	public dispatch( selectedElement: SelectedElement ): void {
		prepareContainer( this.mwWindow.OO, this.mwWindow.$, Dispatcher.APP_DOM_CONTAINER_ID );

		this.app.launch(
			{
				containerSelector: `#${Dispatcher.APP_DOM_CONTAINER_ID}`,
			},
			{
				entityId: selectedElement.entityId,
				propertyId: selectedElement.propertyId,
				editFlow: selectedElement.editFlow,
			},
			createServices( this.mwWindow ), // should be made caching when used repeatedly
		);
	}
}
