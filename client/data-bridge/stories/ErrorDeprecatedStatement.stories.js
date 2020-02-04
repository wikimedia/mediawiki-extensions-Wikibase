import { storiesOf } from '@storybook/vue';
import ErrorDeprecatedStatement from '../src/presentation/components/ErrorDeprecatedStatement';
import useStore from './useStore';

storiesOf( 'ErrorDeprecatedStatement', module )
	.addParameters( { component: ErrorDeprecatedStatement } )
	.addDecorator( useStore( {
		entityTitle: 'Q7186',
		pageTitle: 'Marie_Curie',
		originalHref: 'https://repo.wiki.example/wiki/Item:Q7186?uselang=en',
	} ) )
	.add( 'default', () => ( {
		components: { ErrorDeprecatedStatement },
		template: '<ErrorDeprecatedStatement />',
	} ) );
