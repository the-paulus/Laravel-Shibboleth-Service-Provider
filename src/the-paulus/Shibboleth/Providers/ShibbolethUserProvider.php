<?php namespace ThePaulus\Shibboleth\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Hash;

class ShibbolethUserProvider implements UserProvider {

    /**
     * The user model.
     *
     * @var string
     */
    protected $model;

    /**
     * Create a new Shibboleth user provider.
     *
     * @param  string  $model
     * @return void
     */
    public function __construct($model) {

        $this->model  = $model;

    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     * @return \Illuminate\Auth\Authenticatable | null
     */
    public function retrieveById($identifier) {

        $user = $this->retrieveByCredentials(['id' => $identifier]);

        return ($user && $user->getAuthIdentifier() == $identifier) ?
            $user : null;

    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array $credentials
     * @return Illuminate\Auth\Authenticatable | null
     */
    public function retrieveByCredentials(array $credentials) {

        if (count($credentials) == 0) {

            return null;

        }

        $class = get_class($this->model);
        $user = new $class;

        return $user->where($credentials, '=')->first();

    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Auth\Authenticatable $user
     * @param  array $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $creds) {

      $auth_type = \DB::table('auth_types')->where('id', '=', $creds['auth_type'])->value('name');

      return ($user->auth_type === $auth_type)
            ? true : Hash::check($creds['password'], $user->getAuthPassword());

    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token) {

        // Not Implemented

    }

    /**
     * Retrieve a user by by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Auth\Authenticatable | null
     */
    public function retrieveByToken($identifier, $token) {

        // Not Implemented

    }

}
