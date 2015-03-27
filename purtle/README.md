# Purtle

**Purtle** is a fast, lightweight RDF generator. It provides a "fluent" interface for
generating RDF output in Turtle, XML/RDF or N-Triples. The fluent interface allows the
resulting PHP code to be structured just like Turtle notation for RDF, hence the name: "Purtle"
is a contraction of "PHP Turtle".

The PHP code would look something like this:

    $writer = new TurtleRdfWriter();

    $writer->prefix( 'acme', 'http://acme.test/terms/' );

    $writer->about( 'http://quux.test/Something' )
      ->a( 'acme', 'Thing' )
      ->say( 'acme', 'name' )->text( 'Thingy' )->text( 'Dingsda', 'de' )
      ->say( 'acme', 'owner' )->is( 'http://quux.test/' );


## Release notes

### 0.1 (dev)

Initial release.
