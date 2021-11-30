import { rootModule } from '@/store';
import { defineComponent } from 'vue';
import { Context } from 'vuex-smart-module';

/**
 * Mixin for components that access the state.
 *
 * Basic usage:
 *
 *     export default defineComponent( {
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
const StateMixin = defineComponent( {
	computed: {
		rootModule(): Context<typeof rootModule> {
			return rootModule.context( this.$store );
		},
	},
} );

export default StateMixin;
