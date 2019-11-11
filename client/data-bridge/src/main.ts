import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import Vue from 'vue';
import App from '@/presentation/App.vue';
import AppInformation from '@/definitions/AppInformation';
import AppConfiguration from '@/definitions/AppConfiguration';
import { createStore } from '@/store';
import ServiceRepositories from '@/services/ServiceRepositories';
import Events from '@/events';
import { EventEmitter } from 'events';
import repeater from '@/events/repeater';
import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import DataType from '@/datamodel/DataType';

Vue.config.productionTip = false;

export function launch(
	config: AppConfiguration,
	information: AppInformation,
	services: ServiceRepositories,
): EventEmitter {
	extendVueEnvironment(
		services.getLanguageInfoRepository(),
		services.getMessagesRepository(),
		information.client,
	);

	const store = createStore( services );
	store.dispatch( BRIDGE_INIT, information );

	services.getPropertyDatatypeRepository().getDataType( information.propertyId )
		.then( ( dataType: DataType ) => {
			services.getTracker().trackPropertyDatatype( dataType );
		} );

	const app = new App( {
		store,
	} ).$mount( config.containerSelector );

	const emitter = new EventEmitter();
	repeater( app, emitter, Object.values( Events ) );

	return emitter;
}
