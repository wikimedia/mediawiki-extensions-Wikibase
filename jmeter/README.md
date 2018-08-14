* Run fillowing query in https://query.wikidata.org/

```
SELECT ?entity
WHERE 
{
  ?prop a wikibase:Property; wikibase:directClaim ?p.
  ?entity ?p wd:Q1.
  #minus {?item a wikibase:Property}
  SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }
}

#LIMIT 2
```

* Download results as CSV and put them in the same directory with `test_wd_entity_page_performance.jmx` file.
* Open `test_wd_entity_page_performance.jmx` with Apache JMeter and click "Start" button. 
  Wait untill it finishes.
  Look at "Summary Report" in the left tree.

**Note:** Select part of SPARQL query should be exactly `SELECT ?entity`, otherwise CSV file will be generated with different header and parsing script won't work correctly. 
