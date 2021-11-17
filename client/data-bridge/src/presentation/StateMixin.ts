import { rootModule } from '@/store';
import Vue from 'vue';
import { Context } from 'vuex-smart-module';

/**
 * Mixin for components that access the state.
 *
 * Basic usage:
 *
 *     export default ( Vue as VueConstructor<Vue & InstanceType<typeof StateMixin>> ).extend( {
 *         mixins: [ StateMixin ],
 *         name: 'MyComponent',
 *         methods: {
 *             setValue(): void {
 *                 this.rootModule.dispatch( SET_VALUE, value );
 *             },
 *         },
 *     } );
 *
 */
const StateMixin = Vue.extend( {
	computed: {
		rootModule(): Context<typeof rootModule> {
			return rootModule.context( this.$store );
		},
	},
} );

export default StateMixin;
