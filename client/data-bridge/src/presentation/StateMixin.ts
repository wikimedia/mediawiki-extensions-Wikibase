/* eslint-disable @typescript-eslint/camelcase */
import { rootModule } from '@/store';
import Vue from 'vue';
import Component from 'vue-class-component';
import { Context } from 'vuex-smart-module';

/**
 * Mixin for components that access the state.
 *
 * Basic usage:
 *
 *     class MyComponent extends mixins( StateMixin ) {
 *         public setValue( value ) {
 *             this.rootModule.dispatch( SET_VALUE, value );
 *         }
 *     }
 */
@Component
export default class StateMixin extends Vue {
	private $_StateMixin_rootModule?: Context<typeof rootModule>;

	protected get rootModule(): Context<typeof rootModule> {
		if ( this.$_StateMixin_rootModule === undefined ) {
			this.$_StateMixin_rootModule = rootModule.context( this.$store );
		}
		return this.$_StateMixin_rootModule;
	}
}
