import { storiesOf } from '@storybook/vue';
import ErrorSavingEditConflict from '@/presentation/components/ErrorSavingEditConflict';

storiesOf( 'ErrorSavingEditConflict', module )
	.addParameters( { component: ErrorSavingEditConflict } )
	.add( 'default', () => ( {
		components: { ErrorSavingEditConflict },
		template: `<div style="max-width: 550px; max-height: 550px; border: 1px solid black;">
			<ErrorSavingEditConflict />
		</div>`,
	} ) );
