<?php namespace Acme\Http\Middleware;

use Closure;

/*
* This middleware will set the timezone of the current execution script to 
* one of three options: (1) The users preferred timezone, (2) The timezone the users computer is
* currently set to (if the user is not logged in, or if they haven't set a timezone), or (3) the default
* timezone of the app.
*
* This middleware makes a few assumptions. 
* 1) That you have included the two functions in the helpers.php file somewhere in your code.
* 2) That you have included JQuery.js, Moment.js and jstz-1.0.4.min.js and laravelTimezone.js in your code
* 
* This middleware does not use Laravel's default Cookie class as they are all encrypted. Instead it uses
* the native PHP cookie. This allows cookies set by javascript to be read by the script and visa verca. 
*/
class TimezoneMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /*
         * If the user is logging out, then it expire the cookie. This will allow
         * the computers current default timezone to be used insted of the users
         * preferred timezone. This is helpful for users that have logged in on a 
         * public computer in a different timezone than they normally live in.
         */
        if( $request->getRequestUri() == '/logout' ) {
            setcookie('user_timezone', '', time() - 10, '/', null);
            return $next($request);
        }

        $timezoneCookieName = $this->getTimezoneCookieName();

        // If a timezone is set, either in the cookie or by a user preference, load it.
        if( $timezone = $this->getTimezone()) {
            // Only attempt to update the timezone if it is a PHP supported timezone
            if(timezoneIsSupported($timezone)) {
                \Config::set('app.timezone', $timezone);
                /*
                 * When this Middleware is run, the timezone has already been set by the app.
                 * As a result, along with storing it in the config array for later use
                 * we must also update the timezone within PHP settings so new Carbon dates will be
                 * in the correct timezone.
                 */
                date_default_timezone_set($timezone);
            }
        }

        // Set the timezone cookie to be used on each request
        if( \Auth::check() ) {
            if( \Auth::user()->timezone ) {
                setcookie('user_timezone', \Auth::user()->timezone, (time()+86400*30), '/', null, false);
            } elseif( $timezoneCookieName != 'current_timezone') {
                setcookie('default_timezone', \Config::get('app.timezone'), (time()+86400*30), '/', null, false);
            }
        } elseif( $timezoneCookieName == 'default_timezone' ) {
            setcookie('default_timezone', \Config::get('app.timezone'), (time()+86400*30), '/', null, false);
        }

        return $next($request);
    }

    public function getTimezoneCookieName()
    {
        if( array_key_exists('user_timezone', $_COOKIE) ) {
            return 'user_timezone';
        } elseif( array_key_exists('current_timezone', $_COOKIE) ) {
            return 'current_timezone';
        } else {
            return 'system_timezone';
        }
    }

    public function getTimezone()
    {
        if( array_key_exists('user_timezone', $_COOKIE) ) {
            return $_COOKIE['user_timezone'];
        } elseif( array_key_exists('current_timezone', $_COOKIE) ) {
            return $_COOKIE['current_timezone'];
        } else {
            return \Config::get('app.timezone');
        }
    }

}
