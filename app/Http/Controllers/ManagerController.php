<?php

namespace App\Http\Controllers;

use App\Keywords;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    //

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
}
