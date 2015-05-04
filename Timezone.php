<?php 

use Carbon\Carbon;

/**
 * This trait overrides some of the Eloquent\Model methods. 
 *
 * When this trait is used, it will automatically assume the timezone you want your 
 * dates stored in your database as is UTC. If you want to use a different timezone, simply
 * add the string DATABASE_TIMEZONE to your .env file and it will use that instead.
 *
 * This trait will affect all dates in the $dates array as well as Eloquents built in 
 * default dates of created_at, updated_at, and deleted_at.
 *
 * Dates will still be returned as carbon instances. 
 *
 * toArray() also will return the date/time in the preferred timezone. 
 *
 * If a user wishes to have a preferred timezone, add a field to your users table called 'timezone' (string 155) that will
 * hold the string. If blank, it assumes that it should detect the current timezone based on the users browser. 
 */
trait Timezone {

    protected $defaultTimezone = null;

    public function getDefaultTimezone()
    {
        if( is_null($this->defaultTimezone) ) {
            $this->defaultTimezone = ( \Auth::check() && !empty(\Auth::user()->timezone) ) ? \Auth::user()->timezone : \Config::get('app.timezone');
        }

        return $this->defaultTimezone;
    }

    public function setTimezoneForDates()
    {
        if(empty($this->dates)) {
            return;
        }

        foreach( $this->dates as $column ) {
            $dt = Carbon::createFromFormat('Y-m-d H:i:s',$this->attributes($column), $this->getDefaultTimezone());
            $dt->setTimezone(env('DATABASE_TIMEZONE', 'UTC'));
            $this->attributes[$column] = $dt->format('Y-m-d H:i:s');
        }
    }

    /**
     * Convert a DateTime to a storable string.
     *
     * STOLEN from Illuminate\Database\Eloquent\Model to override.
     * @param  \DateTime|int  $value
     * @return string
     */
    public function fromDateTime($value)
    {
        $format = $this->getDateFormat();

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof DateTime)
        {
            //
        }

        // If the value is totally numeric, we will assume it is a UNIX timestamp and
        // format the date as such. Once we have the date in DateTime form we will
        // format it according to the proper format for the database connection.
        elseif (is_numeric($value))
        {
            $value = Carbon::createFromTimestamp($value, $this->getDefaultTimezone());
        }

        // If the value is in simple year, month, day format, we will format it using
        // that setup. This is for simple "date" fields which do not have hours on
        // the field. This conveniently picks up those dates and format correct.
        elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value))
        {
            $value = Carbon::createFromFormat('Y-m-d', $value, $this->getDefaultTimezone())->startOfDay();
        }

        // If this value is some other type of string, we'll create the DateTime with
        // the format used by the database connection. Once we get the instance we
        // can return back the finally formatted DateTime instances to the devs.
        else
        {
            $value = Carbon::createFromFormat($format, $value, $this->getDefaultTimezone());
        }

        $value->setTimezone(env('DATABASE_TIMEZONE', 'UTC'));
        return $value->format($format);
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * STOLEN from Illuminate\Database\Eloquent\Model to override.
     *
     * @param  mixed  $value
     * @return \Carbon\Carbon
     */
    protected function asDateTime($value)
    {
        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($value))
        {
            $dt = Carbon::createFromTimestamp($value, env('DATABASE_TIMEZONE', 'UTC'));
            $dt->setTimezone($this->getDefaultTimezone());
            return $dt;
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value))
        {
            $dt =  Carbon::createFromFormat('Y-m-d', $value, env('DATABASE_TIMEZONE', 'UTC'))->startOfDay();
            $dt->setTimezone($this->getDefaultTimezone());
            return $dt;
        }

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        elseif ( ! $value instanceof DateTime)
        {
            $format = $this->getDateFormat();

            $dt =  Carbon::createFromFormat($format, $value, env('DATABASE_TIMEZONE', 'UTC'));
            $dt->setTimezone($this->getDefaultTimezone());
            return $dt;
        }

        return Carbon::instance($value);
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = $this->getArrayableAttributes();

        // If an attribute is a date, we will cast it to a string after converting it
        // to a DateTime / Carbon instance. This is so we will get some consistent
        // formatting while accessing attributes vs. arraying / JSONing a model.
        foreach ($this->getDates() as $key)
        {
            if ( ! isset($attributes[$key])) continue;

            $attributes[$key] = (string) $this->asDateTime($attributes[$key]);
        }

        $mutatedAttributes = $this->getMutatedAttributes();

        // We want to spin through all the mutated attributes for this model and call
        // the mutator for the attribute. We cache off every mutated attributes so
        // we don't have to constantly check on attributes that actually change.
        foreach ($mutatedAttributes as $key)
        {
            if ( ! array_key_exists($key, $attributes)) continue;

            $attributes[$key] = $this->mutateAttributeForArray(
                $key, $attributes[$key]
            );
        }

        // Next we will handle any casts that have been setup for this model and cast
        // the values to their appropriate type. If the attribute has a mutator we
        // will not perform the cast on those attributes to avoid any confusion.
        foreach ($this->casts as $key => $value)
        {
            if ( ! array_key_exists($key, $attributes) ||
                in_array($key, $mutatedAttributes)) continue;

            $attributes[$key] = $this->castAttribute(
                $key, $attributes[$key]
            );
        }

        // Here we will grab all of the appended, calculated attributes to this model
        // as these attributes are not really in the attributes array, but are run
        // when we need to array or JSON the model for convenience to the coder.
        foreach ($this->getArrayableAppends() as $key)
        {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }

        return $attributes;
    }

}