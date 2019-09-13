import MwWindow from '@/@types/mediawiki/MwWindow';
import AppBridge from '@/definitions/AppBridge';
import prepareContainer from '@/mediawiki/prepareContainer';
import createServices from '@/mediawiki/createServices';
import { SelectedElement } from '@/mediawiki/SelectedElement';
import subscribeToAppEvents from '@/mediawiki/subscribeToAppEvents';

export default class Dispatcher {
	public static readonly APP_DOM_CONTAINER_ID = 'data-bridge-container';

	private readonly mwWindow: MwWindow;
	private readonly app: AppBridge;
	private readonly tags: string[];

	public constructor( mwWindow: MwWindow, app: AppBridge, tags: string[] = [] ) {
		this.mwWindow = mwWindow;
		this.app = app;
		this.tags = tags;
	}

	public dispatch( selectedElement: SelectedElement ): void {
		const dialog = prepareContainer( this.mwWindow.OO, this.mwWindow.$, Dispatcher.APP_DOM_CONTAINER_ID );

		const emitter = this.app.launch(
			{
				containerSelector: `#${Dispatcher.APP_DOM_CONTAINER_ID}`,
			},
			{
				entityId: selectedElement.entityId,
				propertyId: selectedElement.propertyId,
				editFlow: selectedElement.editFlow,
			},
			createServices( this.mwWindow, this.tags ), // should be made caching when used repeatedly
		);

		subscribeToAppEvents( emitter, dialog.getManager() );
	}
}
