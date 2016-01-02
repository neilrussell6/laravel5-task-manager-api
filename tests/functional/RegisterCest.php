<?php

class RegisterCest
{
    const NAME      = 'Jane Milton';
    const EMAIL     = 'janemilton@hotmail.com';
    const PASSWORD  = 'mypassword';

    /*
    |--------------------------------------------------------------------------
    | Before
    |--------------------------------------------------------------------------
    |
    | Start on Register page.
    |
    */

    public function _before(FunctionalTester $I)
    {
        // given ... I am on register page
        $I->amOnPage('register');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function fill_in_form(FunctionalTester $I)
    {
        $I->fillField('name', self::NAME);
        $I->fillField('email', self::EMAIL);
        $I->fillField('password', self::PASSWORD);
        $I->fillField('password_confirmation', self::PASSWORD);
    }

    /*
    |--------------------------------------------------------------------------
    | Tests
    |--------------------------------------------------------------------------
    |
    | Test routing.
    | Test registration functionality, including redirect and database changes.
    |
    */

    /** @test */
    public function should_correctly_route_to_register(FunctionalTester $I)
    {
        // then ... I should see correct route
        $I->seeCurrentUrlEquals('/register');
    }

    /** @test */
    public function should_redirect_to_home_after_successful_register(FunctionalTester $I)
    {
        // given ... user does not exists

        // when ... I fill out form and submit
        $this->fill_in_form($I);
        $I->click(['class' => 'btn-register']);

        // then .. I should be redirected to home route
        $I->seeCurrentUrlEquals('/home');
    }

    /** @test */
    public function should_add_user_record_after_successful_registration(FunctionalTester $I)
    {
        // given ... user  does not exists

        // when ... I fill out form and submit
        $this->fill_in_form($I);
        $I->click(['class' => 'btn-register']);

        // then ... I should see new user record
        $I->seeRecord('users', ['name' => 'Jane Milton']);
    }
}
