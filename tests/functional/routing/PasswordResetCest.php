<?php

use App\User;

class PasswordResetCest
{
    const NAME          = 'Jimmy Milton';
    const EMAIL         = 'jimmymilton@hotmail.com';
    const PASSWORD_HASH = '$2y$10$rWSLIkALsorAf83/JSWz.enOJiPbgwDigqWAwQknq.c2ZIrUMbzNG';
    const NEW_PASSWORD  = 'mynewpassword';

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

    private function complete_reset_password_email_form(FunctionalTester $I)
    {
        // fill & submit password reset email form
        $I->amOnPage('/password/reset');
        $I->fillField('email', self::EMAIL);
        $I->click(['class' => 'btn-email-password-reset']);
    }

    private function append_token_to_reset_password_route(FunctionalTester $I)
    {
        // get reset token
        $password_reset = $I->grabRecord('password_resets', ['email' => self::EMAIL]);

        // fill password reset form
        $I->amOnPage('/password/reset/' . $password_reset->token);
    }

    private function fill_in_form(FunctionalTester $I)
    {
        $I->fillField('email', self::EMAIL);
        $I->fillField('password', self::NEW_PASSWORD);
        $I->fillField('password_confirmation', self::NEW_PASSWORD);
    }

    /*
    |--------------------------------------------------------------------------
    | Tests
    |--------------------------------------------------------------------------
    |
    | Test routing.
    | Test password reset functionality, including redirect and database changes.
    |
    */

    /** @test */
    public function should_redirect_to_home_after_successful_password_reset(FunctionalTester $I)
    {
        // given ... user exists
        //       ... and I have filled in and submitted the reset_password email form
        //       ... and I have appended the newly created token to the reset password route
        $this->create_user($I);
        $this->complete_reset_password_email_form($I);
        $this->append_token_to_reset_password_route($I);

        // when ... I fill out form and submit
        $this->fill_in_form($I);
        $I->click(['class' => 'btn-reset-password']);

        // then .. I should be redirected to home route
        $I->seeCurrentUrlEquals('/home');
    }

    /** @test */
    public function should_no_longer_see_password_reset_record_for_user_after_successful_password_reset(FunctionalTester $I)
    {
        // given ... user exists
        //       ... and I have filled in and submitted the reset_password email form
        //       ... and I have appended the newly created token to the reset password route
        $this->create_user($I);
        $this->complete_reset_password_email_form($I);
        $this->append_token_to_reset_password_route($I);

        // then ... I should see reset password record for this user
        $I->seeRecord('password_resets', ['email' => self::EMAIL]);

        // when ... I fill out form and submit
        $this->fill_in_form($I);
        $I->click(['class' => 'btn-reset-password']);

        // then .. I should no longer see reset password record for this user
        $I->dontSeeRecord('password_resets', ['email' => self::EMAIL]);
    }
}
