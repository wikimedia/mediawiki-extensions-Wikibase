import Vue from 'vue';
import Vuex from 'vuex';

export default function useStore( state ) {
	return () => {
		Vue.use( Vuex );
		return {
			store: new Vuex.Store( { state } ),
			template: '<story/>',
		};
	};
}
