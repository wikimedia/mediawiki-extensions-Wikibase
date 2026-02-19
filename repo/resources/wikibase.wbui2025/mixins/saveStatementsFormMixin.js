const { mapActions } = require( 'pinia' );

const wbui2025 = require( 'wikibase.wbui2025.lib' );

const saveStatementsFormMixin = {
	methods: Object.assign(
		mapActions( wbui2025.store.useMessageStore, [
			'addStatusMessage'
		] ),
		mapActions( wbui2025.store.useEditStatementsStore, {
			saveChangedStatements: 'saveChangedStatements'
		} ),
		{
			submitFormWithElementRef( currentFormRef ) {
				this.formSubmitted = true;
				const progressTimeout = setTimeout( () => {
					this.showProgress = true;
				}, 300 );
				return this.saveChangedStatements( this.entityId )
					.then( () => {
						this.cancelForm();
						this.addStatusMessage( {
							type: 'success',
							text: mw.msg( 'wikibase-publishing-succeeded' )
						} );
						clearTimeout( progressTimeout );
						this.showProgress = false;
					} )
					.catch( ( errorObj ) => {
						const errorText = wbui2025.util.extractErrorMessage( errorObj, mw.msg( 'wikibase-publishing-error' ) );
						this.addStatusMessage( {
							text: errorText,
							attachTo: currentFormRef,
							type: 'error'
						} );
						clearTimeout( progressTimeout );
						this.showProgress = false;
						this.formSubmitted = false;
					} );
			}
		}
	) };

module.exports = saveStatementsFormMixin;
