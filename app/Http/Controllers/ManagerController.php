<?php

namespace App\Http\Controllers;

use App\Keywords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagerController extends Controller
{
    //

    const LIMIT_ON_PAGE = 10;
    public function index(){
        return view("manager.index");
    }

    public function autocomplete(Request $request){
        $term = $request->get("term");
        $kwords = Keywords::where("keyword","like","$term%")->limit(100)->get();
        $items = array();
        foreach ($kwords as $kw){
            array_push($items,array("value"=>$kw->keyword,"id"=>$kw->id));
        }

        return $items;

    }

    public function getdata(Request $request){
        $term = $request->get("term");
        $kword = Keywords::where("keyword","=","$term")->first();
        return $kword;
    }

    public function getreport($page=0) {
        $result = DB::table('keywords_best')
            ->offset($page*self::LIMIT_ON_PAGE)
            ->limit(self::LIMIT_ON_PAGE)
            ->get();

        return view("manager.report",["result"=>$result]);
    }

    public function getrobotdata(Request $request){
        $result = DB::table('keywords_best')
            //->offset($page*self::LIMIT_ON_PAGE)
            //->limit(self::LIMIT_ON_PAGE)
            ->get();

        return $result;
    }
}
