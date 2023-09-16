<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Route::middleware('auth:sanctum')->group(function (){
Route::get('conversations', [\App\Http\Controllers\ConversationsControllers::class, 'index']);
Route::get('conversations/{conversation}', [\App\Http\Controllers\ConversationsControllers::class, 'show']);
Route::post('conversations/{conversation}/participants', [\App\Http\Controllers\ConversationsControllers::class, 'addParticipant']);
Route::delete('conversations/{conversation}/participants', [\App\Http\Controllers\ConversationsControllers::class, 'removeParticipant']);

Route::get('conversations/{id}/messages', [\App\Http\Controllers\MessagesControllers::class, 'index']);
Route::post('messages', [\App\Http\Controllers\MessagesControllers::class, 'store']);
Route::delete('conversations/{id}/messages', [\App\Http\Controllers\MessagesControllers::class, 'destroy']);
//});
