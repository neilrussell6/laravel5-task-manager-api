<?php

use App\User;

class PasswordResetEmailCest
{
    const NAME          = 'Jimmy Milton';
    const EMAIL         = 'jimmymilton@hotmail.com';
    const PASSWORD      = 'secret';
    const PASSWORD_HASH = '$2y$10$rWSLIkALsorAf83/JSWz.enOJiPbgwDigqWAwQknq.c2ZIrUMbzNG';

    /*
    |--------------------------------------------------------------------------
    | Before
    |--------------------------------------------------------------------------
    |
    | Start with empty log file.
    | Start on Password Reset page.
    |
    */

    public function _before(FunctionalTester $I)
    {
        $I->writeToFile(env('APP_LOG_PATH'), "");

        // given .. I am on password reset page
        $I->amOnPage('password/reset');
    }

    /*
    |--------------------------------------------------------------------------
    | After
    |--------------------------------------------------------------------------
    |
    | Clean log file.
    |
    */

    public function _after(FunctionalTester $I)
    {
        $I->writeToFile(env('APP_LOG_PATH'), "");
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function create_user(FunctionalTester $I)
    {
        factory(User::class, 1)->create([
            'name'      => self::NAME,
            'email'     => self::EMAIL,
            'password'  => self::PASSWORD_HASH
        ]);
    }

    private function fill_in_form(FunctionalTester $I)
    {
        $I->fillField('email', self::EMAIL);
    }

    /*
    |--------------------------------------------------------------------------
    | Tests
    |--------------------------------------------------------------------------
    |
    | Test routing.
    | Test password reset initiate functionality, including redirect,
    | email logging and database changes.
    |
    */

    /** @test */
    public function should_correctly_route_to_password_reset(FunctionalTester $I)
    {
        // then ... I should see correct route
        $I->seeCurrentUrlEquals('/password/reset');
    }

    /** @test */
    public function should_not_redirect_after_form_submit(FunctionalTester $I)
    {
        // given ... user exists
        $this->create_user($I);

        // when ... I fill out form and submit
        $this->fill_in_form($I);
        $I->click(['class' => 'btn-email-password-reset']);

        // then ... I should still be on same route
        $I->seeCurrentUrlEquals('/password/reset');
    }

    /** @test */
    public function should_send_email_with_reset_password_link(FunctionalTester $I)
    {
        // given ... user exists
        $this->create_user($I);

        // when ... I fill out form and submit
        $this->fill_in_form($I);
        $I->click(['class' => 'btn-email-password-reset']);

        // then ... I should see a new log for the password reset email
        $I->openFile(env('APP_LOG_PATH'));
        $I->seeInThisFile('Your Password Reset Link');
        $I->seeInThisFile('jimmymilton@hotmail.com');
    }

    /** @test */
    public function should_add_password_resets_record_on_form_submit(FunctionalTester $I)
    {
        // given ... user exists
        $this->create_user($I);

        // when ... I fill out form and submit
        $this->fill_in_form($I);
        $I->click(['class' => 'btn-email-password-reset']);

        // then ... I should see new password_reset record for user
        $I->seeRecord('password_resets', ['email' => self::EMAIL]);
    }
}
