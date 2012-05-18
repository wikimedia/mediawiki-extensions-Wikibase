require 'spec_helper'

describe "Check if language links are displayed for a specific article" do
  context "Check for display of language links" do
    it "should check that language links are displayed" do
      visit_page(ArticlePage)
      
      @current_page.langLinks?.should be_true
      @current_page.langLinksList?.should be_true
      
      @current_page.count_language_links.should == 3

      @current_page.germanWikiLink?.should be_true
      @current_page.germanWikiLink
      @current_page.wikiArticleTitle.should == WIKI_ARTICLE_TO_TEST
      
    end
  end
end

