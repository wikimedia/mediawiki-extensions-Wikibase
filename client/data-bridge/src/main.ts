import Vue from 'vue';
import App from '@/presentation/App.vue';
import AppInformation from '@/definitions/AppInformation';
import AppConfiguration from '@/definitions/AppConfiguration';
import { createStore } from '@/store';
import ServiceContainer from '@/services/ServiceContainer';
import { initEvents, appEvents } from '@/events';
import { EventEmitter } from 'events';
import repeater from '@/events/repeater';
import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import createServices from '@/services/createServices';

Vue.config.productionTip = false;

export function launch(
	config: AppConfiguration,
	information: AppInformation,
	services: ServiceContainer,
): EventEmitter {
	extendVueEnvironment(
		services.get( 'languageInfoRepository' ),
		services.get( 'messagesRepository' ),
		information.client,
		services.get( 'repoRouter' ),
		services.get( 'clientRouter' ),
	);

	const store = createStore( services );
	store.dispatch( 'initBridge', information );

	const app = new App( {
		store,
	} );
	app.$mount( config.containerSelector );
	app.$on( appEvents.relaunch, () => {
		store.dispatch( 'relaunchBridge', information );
	} );

	const emitter = new EventEmitter();
	repeater( app, emitter, Object.values( initEvents ) );

	return emitter;
}

export { createServices };
