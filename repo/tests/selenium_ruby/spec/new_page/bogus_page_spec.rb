
require 'spec_helper'

describe "Navigate to bogus page" do
  context "this article does not exist" do
    it "should say that the article does not exist" do
      visit_page(BogusPage)
      @current_page.text.should include "Wikipedia does not have an article with this exact name"
      @current_page.text.should include "Other reasons this message may be displayed"
    end
  end
  
  context "follow Create New Article search links" do
    it "should follow all the defined links" do 
      visit_page(BogusPage)
      @current_page.search.should be_empty
      @current_page.text.should include "Search results"
      visit_page(BogusPage)
      @current_page.search2.should be_empty
      @current_page.text.should include "Search results"
    end
  end
  
end
