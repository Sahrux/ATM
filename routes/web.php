<?php

use App\Http\Controllers\AtmController;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Route;

Route::get("/",[MainController::class,"index"]);

Route::prefix("api")->group(function (){
    Route::post("user/create",[AtmController::class,"createUser"]);
    Route::post("user/account",[AtmController::class,"account"]);
    Route::post("permission/create",[AtmController::class,"createPermission"]);
    Route::post("role/create",[AtmController::class,"createRole"]);
    Route::put("role/assign-permissions",[AtmController::class,"assignRolePermissions"]);
    Route::post("banknote/create",[AtmController::class,"createBanknote"]);
    Route::post("withdraw",[AtmController::class,"withdraw"]);
    Route::delete("delete-withdraw",[AtmController::class,"deleteWithdraw"]);

    Route::get("user-and-accounts",[AtmController::class,"getUserAndAccounts"]);
    Route::get("role-and-permissions",[AtmController::class,"getRoleAndPermissions"]);
    Route::get("banknotes",[AtmController::class,"getBanknotes"]);
    Route::get("transactions",[AtmController::class,"getTransactions"]);
});
