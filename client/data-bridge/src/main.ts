import { Component, App } from 'vue';
import RootApp from '@/presentation/App.vue';
import AppInformation from '@/definitions/AppInformation';
import AppConfiguration from '@/definitions/AppConfiguration';
import { createStore } from '@/store';
import ServiceContainer from '@/services/ServiceContainer';
import { appEvents } from '@/events';
import { EventEmitter } from 'events';
import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import createServices from '@/services/createServices';

export function launch(
	createApp: ( rootComponent: Component, rootProps?: Record<string, unknown> | null ) => App,
	config: AppConfiguration,
	information: AppInformation,
	services: ServiceContainer,
): EventEmitter {
	const store = createStore( services );
	store.dispatch( 'initBridge', information );

	const emitter = new EventEmitter();
	const app = createApp( RootApp, { emitter } );
	app.use( store );

	extendVueEnvironment(
		app,
		services.get( 'languageInfoRepository' ),
		services.get( 'messagesRepository' ),
		services.get( 'repoRouter' ),
		services.get( 'clientRouter' ),
	);

	app.mount( config.containerSelector );
	emitter.on( appEvents.relaunch, () => {
		store.dispatch( 'relaunchBridge', information );
	} );

	return emitter;
}

export { createServices };
