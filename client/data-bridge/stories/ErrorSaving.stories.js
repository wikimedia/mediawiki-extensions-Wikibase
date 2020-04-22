import { storiesOf } from '@storybook/vue';
import ErrorSaving from '@/presentation/components/ErrorSaving';
import useStore from './useStore';
import { ErrorTypes } from '@/definitions/ApplicationError';

storiesOf( 'ErrorSaving', module )
	.addParameters( { component: ErrorSaving } )
	.addDecorator( useStore( {
		applicationErrors: [ {
			type: ErrorTypes.SAVING_FAILED,
		} ],
	} ) )
	.add( 'default', () => ( {
		components: { ErrorSaving },
		template: '<ErrorSaving />',
	} ) );
