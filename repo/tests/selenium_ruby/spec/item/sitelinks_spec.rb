require 'spec_helper'

describe "Check functionality of add/edit/remove sitelinks" do

  context "Check for site links UI" do
    it "should check for site links" do
      visit_page(SitelinksItemPage)
      @current_page.sitelinksTable?.should be_true
      @current_page.addSitelinkLink?.should be_true
      @current_page.siteLinkCounter?.should be_true
      
      puts @current_page.siteLinkCounter
      
      
      # @current_page.siteIdCell?.should be_true

      # TODO: implement tests for sitelinks
    end
  end

end

