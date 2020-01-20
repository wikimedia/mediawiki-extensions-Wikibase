import { storiesOf } from '@storybook/vue';
import ErrorUnknown from '@/presentation/components/ErrorUnknown';

storiesOf( 'ErrorUnknown', module )
	.addParameters( { component: ErrorUnknown } )
	.add( 'default', () => ( {
		components: { ErrorUnknown },
		template: '<ErrorUnknown />',
	} ) );
