import MwWindow from '@/@types/mediawiki/MwWindow';
import DataBridgeConfig from '@/@types/wikibase/DataBridgeConfig';
import AppBridge from '@/definitions/AppBridge';
import prepareContainer from '@/mediawiki/prepareContainer';
import { SelectedElement } from '@/mediawiki/SelectedElement';
import subscribeToEvents from '@/mediawiki/subscribeToEvents';

export default class Dispatcher {
	public static readonly APP_DOM_CONTAINER_ID = 'data-bridge-container';

	private readonly mwWindow: MwWindow;
	private readonly app: AppBridge;
	private readonly dataBridgeConfig: DataBridgeConfig;

	public constructor( mwWindow: MwWindow, app: AppBridge, dataBridgeConfig: DataBridgeConfig ) {
		this.mwWindow = mwWindow;
		this.app = app;
		this.dataBridgeConfig = dataBridgeConfig;
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
				entityTitle: selectedElement.entityTitle,
				editFlow: selectedElement.editFlow,
				client: {
					usePublish: this.dataBridgeConfig.usePublish,
				},
			},
			this.app.createServices(
				this.mwWindow,
				this.dataBridgeConfig.editTags,
			), // should be made caching when used repeatedly
		);

		subscribeToEvents( emitter, dialog.getManager() );
	}
}
