<?php

use Illuminate\Support\Facades\Artisan;
use App\User;

class LoginCest
{
    const NAME          = 'John Milton';
    const EMAIL         = 'johnmilton@hotmail.com';
    const PASSWORD      = 'secret';
    const PASSWORD_HASH = '$2y$10$rWSLIkALsorAf83/JSWz.enOJiPbgwDigqWAwQknq.c2ZIrUMbzNG';

    /*
    |--------------------------------------------------------------------------
    | Before
    |--------------------------------------------------------------------------
    |
    | Start on Login page.
    |
    */

    public function _before(FunctionalTester $I)
    {
        Artisan::call('migrate:reset');
        Artisan::call('migrate');
        // given ... I am on login page
        $I->amOnPage('login');
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
        $I->fillField('password', self::PASSWORD);
    }

    /*
    |--------------------------------------------------------------------------
    | Tests
    |--------------------------------------------------------------------------
    |
    | Test routing.
    | Test login functionality, including redirect and database changes.
    |
    */

    /** @test */
    public function should_correctly_route_to_login(FunctionalTester $I)
    {
        // then ... I should see correct route
        $I->seeCurrentUrlEquals('/login');
    }

    /** @test */
    public function should_redirect_to_home_after_successful_login(FunctionalTester $I)
    {
        // given ... user exists
        $this->create_user($I);

        // when ... I fill out form and submit
        $this->fill_in_form($I);
        $I->click(['class' => 'btn-login']);

        // then .. I should be redirected to home route
        $I->seeInCurrentUrl('/home');
    }
}