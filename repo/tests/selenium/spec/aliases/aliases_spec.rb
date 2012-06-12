require 'spec_helper'

describe "Check functionality of add/edit/remove aliases" do

=begin
  context "Check for empty aliases UI" do
    it "should check that there are no aliases" do
      # visit_page(LoginPage)
      # @current_page.login_with(WIKI_USERNAME, WIKI_PASSWORD)

      visit_page(AliasesItemPage)
      # @current_page.create_new_item(generate_random_string(10), generate_random_string(20))
      # @current_page.wait_for_aliases_to_load

      @current_page.aliasesDiv?.should be_false
      @current_page.aliasesTitle?.should be_false
      @current_page.aliasesList?.should be_false
    end
  end
=end

  context "Check for aliases UI" do
    it "should check that aliases work properly" do
      # visit_page(LoginPage)
      # @current_page.login_with(WIKI_USERNAME, WIKI_PASSWORD)

      visit_page(AliasesItemPage)
      # @current_page.create_new_item(generate_random_string(10), generate_random_string(20))
      @current_page.wait_for_aliases_to_load
      initial_num_of_aliases = @current_page.countExistingAliases
      
      @current_page.aliasesDiv?.should be_true
      @current_page.aliasesTitle?.should be_true
      @current_page.aliasesList?.should be_true
      @current_page.editAliases?.should be_true

      # TODO: adding new aliases

      @current_page.editAliases
      @current_page.editAliases?.should be_false
      @current_page.cancelAliases?.should be_true
      @current_page.aliasesTitle?.should be_true
      @current_page.aliasesList?.should be_true
      @current_page.aliasesInputEmpty?.should be_true
      # @current_page.aliasesInputModified?.should be_false
      @current_page.cancelAliases
      @current_page.countExistingAliases.should == initial_num_of_aliases

      @current_page.aliasesDiv?.should be_true
      @current_page.aliasesTitle?.should be_true
      @current_page.aliasesList?.should be_true
      @current_page.editAliases?.should be_true

      @current_page.editAliases
      @current_page.aliasesInputEmpty?.should be_true
      @current_page.aliasesInputEmpty= "new alias"
      @current_page.aliasesInputModified?.should be_true

    end
  end

end

