<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Companies extends Model
{

    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_name', 'company_logo'
    ];

    public function getCompanyLogoAttribute($logo)
    {
    	return ($logo) ? (env('APP_URL') . '/storage/companies/' . $logo) : null;
    }
}