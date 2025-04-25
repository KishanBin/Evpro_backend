<?php


use Illuminate\Http\Request;


Route::get('/hi', function () {
    return "hello kishan";
});


Route::get('/', function () {
    return view('welcome');
});
