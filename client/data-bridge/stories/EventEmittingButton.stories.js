import { storiesOf } from '@storybook/vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';

storiesOf( 'EventEmittingButton', module )
	.addParameters( { component: EventEmittingButton } )
	.add( 'primaryProgressive L', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				message="primaryProgressive"
			/>`,
	} ) )
	.add( 'primaryProgressive as link', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				message="primaryProgressive"
				href="https://www.mediawiki.org/wiki/Wikidata_Bridge"
				:preventDefault="false"
			/>`,
	} ) )
	.add( 'primaryProgressive as link opening in new tab', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				message="primaryProgressive"
				href="https://www.mediawiki.org/wiki/Wikidata_Bridge"
				:newTab="true"
				:preventDefault="false"
			/>`,
	} ) )
	.add( 'squary primaryProgressive', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				:squary="true"
				message="squary primaryProgressive"
			/>`,
	} ) )
	.add( 'primaryProgressive M', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="M"
				message="primaryProgressive M"
			/>`,
	} ) )
	.add( 'primaryProgressive XL', () => ( {
		components: { EventEmittingButton },
		template: `<EventEmittingButton
			type="primaryProgressive"
			size="XL"
			message="primaryProgressive XL"
		/>`,
	} ) )
	.add( 'primaryProgressive M full-width', () => ( {
		components: { EventEmittingButton },
		template:
			`<div style="max-width: 25em; padding: 2em; border: 1px solid black;">
				<EventEmittingButton
					type="primaryProgressive"
					size="M"
					message="primaryProgressive M"
					style="width: 100%"
				/>
			</div>`,
	} ) )
	.add( 'close M', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="close"
				size="M"
				message="close"
			/>`,
	} ) )
	.add( 'close L', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="close"
				size="L"
				message="close"
			/>`,
	} ) )
	.add( 'close XL', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="close"
				size="XL"
				message="close"
			/>`,
	} ) )
	.add( 'close squary', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="close"
				size="L"
				:squary="true"
				message="close"
			/>`,
	} ) )
	.add( 'primaryProgressive disabled', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				message="disabled primaryProgressive"
				:disabled="true"
			/>`,
	} ) )
	.add( 'close disabled', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="close"
				size="L"
				message="disabled close"
				:disabled="true"
			/>`,
	} ) )
	.add( 'neutral M', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="neutral"
				size="M"
				message="Go back"
			/>`,
	} ) )
	.add( 'back L', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="back"
				size="L"
				message="back"
			/>`,
	} ) )
	.add( 'back RTL', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				style="transform: scaleX( -1 );"
				type="back"
				size="L"
				message="back"
			/>`,
	} ) )
	.add( 'link M', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="link"
				size="M"
				message="Keep editing"
			/>`,
	} ) )
	.add( 'link M disabled', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="link"
				size="M"
				message="Keep editing"
				:disabled="true"
			/>`,
	} ) );
