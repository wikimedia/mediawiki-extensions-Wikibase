import Vue, { ComponentOptions } from 'vue';

declare module 'vue/types/vue' {
	interface App {
		use( plugin: unknown ): App;
		mount( elementOrSelector: Element | string ): App;
	}

	interface VueConstructor {
		createMwApp( componentOptions: ComponentOptions<Vue>, propsData?: object ): App;
	}
}
