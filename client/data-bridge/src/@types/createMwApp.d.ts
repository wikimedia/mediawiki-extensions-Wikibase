import Vue, { ComponentOptions } from 'vue';

declare module 'vue/types/vue' {
	interface App extends Vue {
		use( plugin: unknown, ...args: unknown[] ): App;

		mount( elementOrSelector: Element | string ): App;
	}

	interface VueConstructor {
		createMwApp( componentOptions: ComponentOptions<Vue>, propsData?: object ): App;
	}
}
