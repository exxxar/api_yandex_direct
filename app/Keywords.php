<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Keywords extends Model
{
    protected $table = "keywords";

    public function AuctionBids()
    {
        return $this->hasMany('App\AuctionBids');
    }

    public function ContextCoverage()
    {
        return $this->hasMany('App\ContextCoverage');
    }

    public function CompetitorsBids()
    {
        return $this->hasMany('App\CompetitorsBids');
    }
}
