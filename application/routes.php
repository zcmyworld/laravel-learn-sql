<?php

return array(
    'GET /' => function()
    {
//        $rs = \System\DB::query("select * from user where id = 1");
//        $rs = \System\DB::query("select * from user where id = ?", array(3));
//        $rs = \System\DB::query("update user set name = ? where id = ?", array("content22", 2));
        
//        $rs = \System\DB::table('user')->select("id", "name")->first();
//        DB::table('user')->get();

        $rs = DB::table('user')->select("*")->where('id', '=', 1)->get();

//        DB::table('user')->insert(array(
//            "id" => 5,
//            "name" => "content5"
//        ));
        
//        DB::table('user')->where('id', '=', 2)->update(
//            array(
//                "name" => "helloworld"
//            )
//        );
//        var_dump($rs);
        return View::make('home/index')->bind("key", "Let's learn laravel!");
    }
);
