<?php
namespace ThePaulus\Shibboleth\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Orchestra\Testbench\Traits\CreatesApplication;
use Symfony\Component\HttpFoundation\Response;
use ThePaulus\Shibboleth\Providers\ShibbolethServiceProvider;
use ThePaulus\Shibboleth\Controllers\ShibbolethController;

class ShibbolethTest extends TestCase {

    use CreatesApplication, RefreshDatabase;

    protected function setUp()
    {
        parent::setUp();

        $realpath = realpath(__DIR__ . '/../src/database/migrations');
        $this->loadMigrationsFrom($realpath);

    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('shibboleth', require __DIR__ . '/../src/config/shibboleth.php');

        $app['config']->set('auth', [
            'defaults' => [
                'guard' => 'web',
                'passwords' => 'users',
            ],
            'guards' => [
                'web' => [
                    'driver' => 'session',
                    'provider' => 'users',
                ],
                'api' => [
                    'driver' => 'token',
                    'provider' => 'users',
                ],
            ],
            'providers' => [
                'users' => [
                    'driver' => 'shibboleth',
                    'model' => User::class,
                ],
            ],
        ]);

    }

    protected function getPackageProviders($app) {

        return [
            ShibbolethServiceProvider::class,
            ConsoleServiceProvider::class   // For migrations
        ];

    }

    /**
     * Tests the migrations associated with the package.
     *
     * @group database
     * @group migration
     */
    public function testDatabaseMigrations() {

        User::create([
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $row = \DB::table('users')->where('id', 1)->first();

        $this->assertNotNull($row);

    }

    public function testCreateUser() {

        $user = User::where('email', 'user@example.com')->first();

        $this->assertEmpty($user);

        User::create([
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $user = User::where('email', 'user@example.com')->first();

        $this->assertInstanceOf(User::class, $user);

        (new ShibbolethController)->idpAuthorize();

    }

    public function testRoutes() {

        $this->get('/login')->assertStatus(Response::HTTP_OK);

    }
}
