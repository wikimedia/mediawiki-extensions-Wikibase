import Vue from 'vue';
import Vuex from 'vuex';
import { rootModule } from '@/store';
import { createStore as smartCreateStore } from 'vuex-smart-module';

export default function useStore( state ) {
	return () => {
		Vue.use( Vuex );
		rootModule.options.state = class {
			constructor() {
				Object.assign( this, state );
			}
		};
		const store = smartCreateStore( rootModule );
		return {
			store,
			template: '<story/>',
		};
	};
}
