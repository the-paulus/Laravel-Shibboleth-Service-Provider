<?php
namespace ThePaulus\Shibboleth\Controllers;

use App\Models\User;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Console\AppNamespaceDetectorTrait;
use JWTAuth;

class ShibbolethController extends Controller
{
    /**
     * Service Provider
     * @var Shibalike\SP
     */
    private $sp;

    /**
     * Identity Provider
     * @var Shibalike\IdP
     */
    private $idp;

    /**
     * Configuration
     * @var Shibalike\Config
     */
    private $config;

    /**
     * Constructor
     */
    public function __construct(GenericUser $user = null)
    {
        if (config('shibboleth.emulate_idp') == true) {
            $this->config         = new \Shibalike\Config();
            $this->config->idpUrl = '/emulated/idp';

            $stateManager = $this->getStateManager();

            $this->sp = new \Shibalike\SP($stateManager, $this->config);
            $this->sp->initLazySession();

            $this->idp = new \Shibalike\IdP($stateManager, $this->getAttrStore(), $this->config);
        }

        $this->user = $user;
    }

    /**
     * Create the session, send the user away to the IDP
     * for authentication.
     */
    public function create()
    {
        if (env('EMULATE_SHIBBOLETH', config('shibboleth.emulate_idp')) === TRUE) {

            return Redirect::to(action('\\' . __CLASS__ . '@emulateLogin')
                . '?target=' . action('\\' . __CLASS__ . "@emulateIdp"));

        } else {

            return Redirect::to('https://' . Request::server('SERVER_NAME')
                . ':' . Request::server('SERVER_PORT')
                . config('shibboleth.idp_login')
                . '?target=' . action('\\' . __CLASS__ . '@idpAuthorize'));

        }
    }

    /**
     * Alias for create()
     */
    public function login() {

        $this->create();

    }

    /**
     * Login for users not using the IdP.
     */
    public function localCreate()
    {
        return $this->viewOrRedirect(config('shibboleth.local_login'));
    }

    /**
     * Alias for localCreate()
     */
    public function localLogin() {

        $this->localCreate();

    }

    /**
     * Authorize function for users not using the IdP.
     */
    public function localAuthorize()
    {
        $email    = Input::get(config('shibboleth.local_login_user_field'));
        $password = Input::get(config('shibboleth.local_login_pass_field'));
        $userClass  = config('auth.providers.users.model', 'App\\User');
        $groupClass = config('auth.providers.groups.model', 'App\\Group');

        if (Auth::attempt(array('email' => $email, 'password' => $password, 'auth_type' => 'local'), true)) {

            $user = $userClass::where('email', '=', $email)->first();

            return $this->viewOrRedirect(
                $this->tokenizeDestination(
                    config('shibboleth.local_authenticated'),
                    $user,
                    ['auth_type' => 'local']
                ));

        } else {

            Session::flash('message', 'Invalid email or password.');

            return $this->viewOrRedirect(config('shibboleth.local_login'));

        }
    }

    /**
     * Setup authorization based on returned server variables
     * from the IdP.
     */
    public function idpAuthorize()
    {
        $email      = $this->getServerVariable(config('shibboleth.idp_login_email'));
        $first_name = $this->getServerVariable(config('shibboleth.idp_login_first'));
        $last_name  = $this->getServerVariable(config('shibboleth.idp_login_last'));
        $userClass  = config('auth.providers.users.model', 'App\\User');
        $groupClass = config('auth.providers.groups.model', 'App\\UserGroup');

        // Attempt to login with the email, if success, update the user model
        // with data from the Shibboleth headers (if present)
        // TODO: This can be simplified a lot
        if (Auth::attempt(array('email' => $email, 'auth_type' => 'shibboleth'), true)) {

            $user = $userClass::where('email', '=', $email)->first();

            // Update the model as necessary
            if (isset($first_name)) {
                $user->first_name = $first_name;
            }

            if (isset($last_name)) {
                $user->last_name = $last_name;
            }

            $user->save();

            // We need to pass the token... how?
            // Let's try this.
            return $this->viewOrRedirect(
                $this->tokenizeDestination(
                    config('shibboleth.shibboleth_authenticated'),
                    $user,
                    ['auth_type' => 'idp']
                ));

        } else {
            //Add user to group and send through auth.
            if (isset($email)) {

                if (config('shibboleth.add_new_users', false)) {

                    try {

                        $user = $userClass::firstOrCreate(array(
                            'email'      => $email,
                            'first_name' => $first_name,
                            'last_name'  => $last_name
                        ));

                        $shibboleth_group = config('shibboleth.shibboleth_group', 'UserGroup');

                        if(is_int($shibboleth_group)) {

                            $group = $groupClass::findOrFail(intval($shibboleth_group));

                        } else {

                            $group = $groupClass::firstOrFail(['name' => $shibboleth_group]);

                        }

                        if(!$group->hasMember($user)) {

                            $group->users()->save($user);

                        }

                    } catch (ModelNotFoundException $modelNotFoundException) {

                        throw new \RuntimeException("Could not find " . $groupClass
                            . " with primary key " . config('shibboleth.shibboleth_group')
                            . "! Check your Laravel-Shibboleth configuration.",
                            900,
                            $modelNotFoundException);

                    } catch(\PDOException $PDOException) {

                        throw new \RuntimeException($PDOException->getMessage(),
                            $PDOException->getCode());

                    }

                    // this is simply brings us back to the session-setting branch directly above
                    if (env('EMULATE_SHIBBOLETH', config('shibboleth.emulate_idp')) === true) {

                        return Redirect::to(action('\\' . __CLASS__ . '@emulateLogin')
                            . '?target=' . action('\\' . __CLASS__ . '@emulateIdp'));

                    } else {

                        return Redirect::to('https://' . Request::server('SERVER_NAME')
                            . ':' . Request::server('SERVER_PORT') . config('shibboleth.idp_login')
                            . '?target=' . action('\\' . __CLASS__ . '@idpAuthorize'));

                    }

                } else {
                    // TODO: This is old... it will just cause redirect loops.
                    // Identify that the user was not in our database and will not be created (despite passing IdP)
                    Session::put('auth_type', 'no_user');
                    Session::put('group', 'undefined');

                    return $this->viewOrRedirect(config('shibboleth.shibboleth_unauthorized'));
                }
            }
        }
    }

    /**
     * Destroy the current session and log the user out, redirect them to the main route.
     */
    public function destroy()
    {

        $auth_type = Auth::user()->auth_type;

        Auth::logout();
        Session::flush();

        if(env('JWTAUTH')) {

            JWTAuth::parseToken()->invalidate();

        }

        if($auth_type == 'idp') {

            if (config('shibboleth.emulate_idp') == true) {

                return Redirect::to(action(__CLASS__ . '@emulateLogout'));

            } else {

                return Redirect::to('https://' . Request::server('SERVER_NAME') . config('shibboleth.idp_logout'));

            }

        } else {

            return $this->viewOrRedirect(config('shibboleth.local_logout'));

        }
    }

    /**
     * Emulate a login via Shibalike
     */
    public function emulateLogin()
    {
        $from = (Input::get('target') != null) ? Input::get('target') : $this->getServerVariable('HTTP_REFERER');

        $this->sp->makeAuthRequest($from);
        $this->sp->redirect();
    }

    /**
     * Emulate a logout via Shibalike
     */
    public function emulateLogout()
    {
        $this->sp->logout();

        Redirect::to(env('URL', $this->getServerVariable('HTTP_REFERER')));

    }

    /**
     * Emulate the 'authorization' via Shibalike
     */
    public function emulateIdp()
    {
        $data = [];

        if (Input::get('username') != null) {
            $username = (Input::get('username') === Input::get('password')) ?
                Input::get('username') : '';

            $userAttrs = $this->idp->fetchAttrs($username);
            if ($userAttrs) {
                $this->idp->markAsAuthenticated($username);
                $this->idp->redirect('/idp');
            }

            $data['error'] = 'Incorrect username and/or password';
        }

        return view('IdpLogin', $data);
    }

    /**
     * Function to get an attribute store for Shibalike
     */
    private function getAttrStore()
    {
        return new \Shibalike\Attr\Store\ArrayStore(config('shibboleth.emulate_idp_users'));
    }

    /**
     * Gets a state manager for Shibalike
     */
    private function getStateManager()
    {
        $session = \UserlandSession\SessionBuilder::instance()
            ->setSavePath(sys_get_temp_dir())
            ->setName('SHIBALIKE_BASIC')
            ->build();
        return new \Shibalike\StateManager\UserlandSession($session);
    }

    /**
     * Wrapper function for getting server variables.
     * Since Shibalike injects $_SERVER variables Laravel
     * doesn't pick them up. So depending on if we are
     * using the emulated IdP or a real one, we use the
     * appropriate function.
     */
    private function getServerVariable($variableName)
    {
        if (config('shibboleth.emulate_idp') == true) {
            return isset($_SERVER[$variableName]) ?
                $_SERVER[$variableName] : null;
        } else {
            return (!empty(Request::server($variableName))) ?
                Request::server($variableName) :
                Request::server('REDIRECT_' . $variableName);
        }
    }

    /*
     * Simple function that allows configuration variables
     * to be either names of views, or redirect routes.
     */
    private function viewOrRedirect($view)
    {
        return (View::exists($view)) ? view($view) : Redirect::to($view);
    }

    /**
     * Appends a user's token to the provided URL.
     *
     * @param string    $url    URL to append the token parameter to.
     * @param User      $user   Owner of the token to be appended.
     * @param array     $claims Additional claims for the token.
     *
     * @return string   URL with a token parameter.
     */
    private function tokenizeDestination(string $url, $user, $claims) {

        $token = '';

        if(env('JWTAUTH')) {

            $token = '?token=' . JWTAuth::fromUser($user, $claims);
        }

        return $url . $token;

    }
}
