<?php

namespace Dengrweb\DAuth\Tests\Feature;

use Dengrweb\DAuth\DAuth;
use Dengrweb\DAuth\Tests\TestCase;

class RoutesDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('dauth.routes', false);
    }

    public function test_routes_can_be_turned_off_for_the_accounts_server_itself()
    {
        $this->get('/auth/dauth/redirect')->assertNotFound();

        $this->assertNull(DAuth::sharedProps()['loginUrl']);
        $this->assertNotEmpty(DAuth::sharedProps()['services']);
    }
}
