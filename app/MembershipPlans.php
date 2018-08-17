<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MembershipPlans extends Model
{
    const PLAN_LIMITED_1_MONTH = 1;
    const PLAN_UNLIMITED = 25;

    public function isLimited()
    {
        return ($this->id !== MembershipPlans::PLAN_UNLIMITED);
    }
}