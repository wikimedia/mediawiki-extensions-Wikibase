( function () {
  var form = $( 'form#mw-newentity-form1' );

  form.submit( function () {
    $( this).find( "button[type='submit']" ).prop( 'disabled',true );
  } );

} )( jQuery, mediaWiki );
