class LabelPage
  include PageObject

  #page_url 'http://en.wikipedia.beta.wmflabs.org/w/index.php?title=Special:UserLogin'
  page_url 'http://localhost/wiki/index.php/Data:Q5'
  h1(:firstHeading, :id => 'firstHeading')

end


