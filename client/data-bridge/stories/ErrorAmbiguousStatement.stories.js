import { storiesOf } from '@storybook/vue';
import ErrorAmbiguousStatement from '../src/presentation/components/ErrorAmbiguousStatement';
import useStore from './useStore';

storiesOf( 'ErrorAmbiguousStatement', module )
	.addParameters( { component: ErrorAmbiguousStatement } )
	.addDecorator( useStore( {
		entityTitle: 'Q7186',
		pageTitle: 'Marie_Curie',
		originalHref: 'https://repo.wiki.example/wiki/Item:Q7186?uselang=en',
	} ) )
	.add( 'default', () => ( {
		components: { ErrorAmbiguousStatement },
		template: '<ErrorAmbiguousStatement />',
	} ) );
