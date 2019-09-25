import App from '@/presentation/App.vue';

export function launch(): void {
	new App().$mount( '.wikibase-tainted-references-container' );
}
