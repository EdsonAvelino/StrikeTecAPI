<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ComboTags extends Model
{
	public function filter()
	{
		return $this->belongsTo('App\TagFilters');
	}
}
