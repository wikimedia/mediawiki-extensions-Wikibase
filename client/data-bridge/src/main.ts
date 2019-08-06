import MwWindow from '@/@types/mediawiki/MwWindow';
import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import SpecialPageEntityRepository from '@/data-access/SpecialPageEntityRepository';
import { services } from '@/services';
import Vue from 'vue';
import App from '@/presentation/App.vue';
import ApplicationConfig from '@/definitions/ApplicationConfig';
import AppInformation from '@/definitions/AppInformation';
import { createStore } from '@/store';

Vue.config.productionTip = false;

export function launch( applicationConfig: ApplicationConfig, information: AppInformation ): void {

	services.setEntityRepository( new SpecialPageEntityRepository(
		( window as MwWindow ).$,
		applicationConfig.specialEntityDataUrl,
	) );

	const store = createStore();
	store.dispatch( BRIDGE_INIT, information );

	new App( {
		store,
	} ).$mount( applicationConfig.containerSelector );
}
