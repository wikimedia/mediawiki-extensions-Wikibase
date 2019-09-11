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

Vue.config.productionTip = false;

export function launch(
	config: AppConfiguration,
	information: AppInformation,
	services: ServiceRepositories,
): void {
	Vue.directive( 'inlanguage', inlanguage( services.getLanguageInfoRepository() ) );
	const store = createStore( services );
	store.dispatch( BRIDGE_INIT, information );

	new App( {
		store,
	} ).$mount( config.containerSelector );
}
