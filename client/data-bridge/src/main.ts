import Vue, { CreateElement } from 'vue';
import App from '@/presentation/App.vue';
import AppInformation from '@/definitions/AppInformation';
import AppConfiguration from '@/definitions/AppConfiguration';
import { createStore } from '@/store';
import ServiceContainer from '@/services/ServiceContainer';
import { appEvents } from '@/events';
import { EventEmitter } from 'events';
import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import createServices from '@/services/createServices';

Vue.config.productionTip = false;

export function launch(
	config: AppConfiguration,
	information: AppInformation,
	services: ServiceContainer,
): EventEmitter {
	const store = createStore( services );
	store.dispatch( 'initBridge', information );

	const emitter = new EventEmitter();

	const compatApp = {
		store,
		render( h: CreateElement ) {
			return h( App, { props: { emitter } } );
		},
	};

	const app = Vue.createMwApp( compatApp );

	extendVueEnvironment(
		app,
		services.get( 'languageInfoRepository' ),
		services.get( 'messagesRepository' ),
		information.client,
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
