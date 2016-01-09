<?php

class IndexCest
{
    /** @test */
    public function should_correctly_route_to_index(FunctionalTester $I)
    {
        // given .. I am on index page
        $I->amOnPage('/');

        // then ... I should see correct route
        $I->seeCurrentUrlEquals('/');
    }
}
