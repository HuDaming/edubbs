<?php

Route::get('/', 'PagesController@root')->name('root');

Auth::routes();

Route::resource('users', 'UsersController', ['only' => ['show', 'update', 'edit']]);