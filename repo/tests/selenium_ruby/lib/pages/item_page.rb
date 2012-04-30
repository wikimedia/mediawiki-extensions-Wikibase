class ItemPage
  include PageObject
  item_id = 'q10';
  
  page_url 'http://localhost/mediawiki/index.php/Data:Q10'
  h1(:firstHeading, :id => 'firstHeading')
  span(:itemLabelSpan, :xpath => "//h1[@id='firstHeading']/span");
end


