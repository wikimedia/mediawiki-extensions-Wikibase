import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import Vue from 'vue';
import App from '@/presentation/App.vue';
import AppInformation from '@/definitions/AppInformation';
import AppConfiguration from '@/definitions/AppConfiguration';
import { createStore } from '@/store';
import ServiceRepositories from '@/services/ServiceRepositories';
import inlanguage from '@/presentation/directives/inlanguage';
import MessagesPlugin from '@/presentation/plugins/MessagesPlugin';
import Events from '@/events';
import { EventEmitter } from 'events';
import repeater from '@/events/repeater';

Vue.config.productionTip = false;

export function launch(
	config: AppConfiguration,
	information: AppInformation,
	services: ServiceRepositories,
): EventEmitter {
	Vue.directive( 'inlanguage', inlanguage( services.getLanguageInfoRepository() ) );
	Vue.use( MessagesPlugin, services.getMessagesRepository() );

	const store = createStore( services );
	store.dispatch( BRIDGE_INIT, information );

	const app = new App( {
		store,
	} ).$mount( config.containerSelector );

	const emitter = new EventEmitter();
	repeater( app, emitter, Object.values( Events ) );

	return emitter;
}
