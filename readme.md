#LaravelTimezone
---

#### Built for Laravel 5.0.\*

LaravelTimezone is a small set of files that you include in your laravel application that will allow your application to manage timezones for your applications. It gives you the ability to detect the users current timezone as set in their computer and display all dates/times in the users current timezone. When persisting to a database it converts to UTC time (or a pre-defined timezone you set in your **.env** file).

LaravelTimezone is in no way endorsed by Laravel. It's named as such because this implementation is specific to Laravel. 

## Setup
---
####Javascript
In order to use LaravelTimezone you will need to include the following javascript files. 

* [JQuery](http://jquery.com)
* [Moment.js](http://momentjs.com/)
* [jsTimezoneDetect](https://bitbucket.org/pellepim/jstimezonedetect)
* laravelTimezone.js

Include the above Javascript files in your views. `laravelTimezone.js` will automatically fire a function on `document.ready` to determine the users timezone as a PHP string and will then create a cookie that your Laravel app can use.


####Middleware
Place **TimezoneMiddleware.php** in your `app\Http\Middleware` directory. Make sure you adjust any needed Namespace. Then open **app\Http\Kernal.php** and include the new Middleware in your `$middleware` array. 

```
'Acme\Http\Middleware\TimezoneMiddleware',
```

####Helpers
There are some required helpers in `helpers.php` that will need to be included in your app. You can either copy and paste them into an existing helpers file, or you can update your composer.json file to automatically load the file. 

```
"autoload": {
	"files": [
          "app/Helpers/helpers.php"
        ]
	}
```

####Eloquent Models
In order to have your eloquent models convert date/time stored in your database to the users timezone (and back before persisting), simply include the `Timezone.php` trait on any models you want this functionality on.

```
class User extends Model implements AuthenticatableContract, CanResetPasswordContract {

	use Authenticatable, CanResetPassword, SoftDeletes, Timezone;
	
	// ... rest of class logic

}
```

## Configuring
---
#### Default Database Timezone
By default laravelTimezone assumes you want your dates stored in UTC. However you can override this by placing the following in your **.env** file

```
DATABASE_TIMEZONE=America/Denver
```

Note that this does not modify any settings on your database server. It merely converts to the timezone prior to persisting.

####User Selected Timezone
If you would like your users to be able to choose a default timezone you can add a column to the users table called `timezone` that is a string (length of 155).

If the field is blank, then the laravelTimezone will use the users current timezone as set on their computer. If there is no default timezone set due to cookie restrictions, then it defaults timezones to the App's default timezone. 

## Helpers & Limitations
---
####Helpers
The Helpers file which is required for the Middleware to function properly, also has a great function that will return an array of PHP supported timezones. This is useful for buidling a dropdown menu. 

You have two options you can pass to the function `'asArray'` will return an array containing numerical keys with the value for each key being the string of a supported PHP timezone. 

If you pass `'asKeys'` then an array will be returned with the keys of the array being a string of a supported PHP timezone and the value will have the current UTC Offset (adjusting for DST) along with the name of the timezone. This is most useful for building dropdown forms. 

####Limitations

**Requires Cookies**

If the user has cookies disabled or restricted on the site, then the timezone can't be detected automatically. In this case it will revert to the timezone setting in `config\app.php`. Although you can still set timezones for logged in users via the `User` class. 


**Not a Package**

I am just starting with Laravel, so I am not too familiar. I am currently developing 2 apps in it, both of which needed timezone support. Because of the Javascript requirements I am resistent to try and make it a package that can be brought in via Composer. 


**Overrides Eloquent\Model Methods**

I really am not a fan of overriding existing methods. While this works fine for now, if those methods are modified in the future, I'll have to re-modify them to have them work with the Timezone functionality. If there were two more events (one for `hydrating` and one for `arraying`) that I could hook onto then I wouldn't need to override any of the existing methods. 




