
require 'spec_helper'

describe "Check functionality of login" do
  context "Check for login" do
    it "should check for correct login" do
      visit_page(LoginPage)
      @current_page.login_with("tobijat", "darthvader")
    end
  end
end
