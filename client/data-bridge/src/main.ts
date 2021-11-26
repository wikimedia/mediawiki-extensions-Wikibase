import Vue from 'vue';
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
	extendVueEnvironment(
		services.get( 'languageInfoRepository' ),
		services.get( 'messagesRepository' ),
		information.client,
		services.get( 'repoRouter' ),
		services.get( 'clientRouter' ),
	);

	const store = createStore( services );
	store.dispatch( 'initBridge', information );

	const emitter = new EventEmitter();
	const app = new App( {
		store,
		propsData: { emitter },
	} );
	app.$mount( config.containerSelector );
	emitter.on( appEvents.relaunch, () => {
		store.dispatch( 'relaunchBridge', information );
	} );

	return emitter;
}

export { createServices };
