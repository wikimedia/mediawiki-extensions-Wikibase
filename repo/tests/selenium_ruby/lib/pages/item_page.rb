class ItemPage
  include PageObject
  #def initialize(item_id)
  #  @item_id = item_id
  #end
  item_id = '10';
  
  #page_url "http://localhost/mediawiki/index.php/Data:q#{@item_id}"
  page_url "http://localhost/mediawiki/index.php/Data:q" + item_id
  h1(:firstHeading, :id => 'firstHeading')
  span(:itemLabelSpan, :xpath => "//h1[@id='firstHeading']/span");
end
