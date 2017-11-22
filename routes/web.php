<?php

Route::group(['prefix' => 'test'], function () {
    Route::group(['prefix' => 'apidirect'], function () {
        Route::get('/','ApiDirectController@main');

        Route::group(['prefix' => 'dictionary'], function () {
            Route::get('/get/{param}','ApiDirectController@getDictionary');
        });

        Route::group(['prefix' => 'campaing'], function () {
            Route::get('/','ApiDirectController@campaingMain');
            Route::post('/add','ApiDirectController@addCampain');
            Route::get('/get/{ids}','ApiDirectController@getCampain');
            Route::get('/remove/{id}','ApiDirectController@removeCampain');
        });

        Route::group(['prefix' => 'groups'], function () {
            Route::get('/','ApiDirectController@groupsMain');
            Route::get('/list/{ids}','ApiDirectController@getCampaingGroups');
            Route::get('/add/{id}','ApiDirectController@addGroupPage');
            Route::post('/add','ApiDirectController@addGroup');
            Route::get('/get/{ids}','ApiDirectController@getGroup');
            Route::get('/remove/{groupId}/{campaingId}','ApiDirectController@removeGroup');
        });

        Route::group(['prefix' => 'bids'], function () {
            Route::get('/','ApiDirectController@bidsMain');
            Route::post('/add','ApiDirectController@addBids');
            Route::get('/get/{groupId}','ApiDirectController@getBids');
           // Route::get('/remove/{groupId}/{campaingId}','ApiDirectController@removeGroup');
        });


        Route::group(['prefix' => 'wordstat'], function () {
            Route::post('/create','ApiDirectController@createNewWordstatReport');
            Route::get('/delete/{id}/{groupId}','ApiDirectController@deleteWordstatReport');
            Route::get('/report/list','ApiDirectController@getWordstatReportList');
            Route::get('/report/{id}/{groupId}','ApiDirectController@getWordstatReport');
        });

        Route::group(['prefix' => 'keywords'], function () {
            Route::get('/list/{groupId}','ApiDirectController@getKeywordsList');
            Route::get('/add/{groupId}','ApiDirectController@addKeywordsPage');
            Route::post('/add','ApiDirectController@addKeywords');
            Route::get('/remove/{keywordId}/{groupId}','ApiDirectController@removeKeyword');

        });

    });
});


Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
