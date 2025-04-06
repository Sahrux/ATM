<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Banknote;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AtmController extends Controller
{

    public function createUser(Request $request)
    {
        $check_token = $this->checkToken($request);
        if(!$check_token){
            return response()->json([
                "status" => "ERROR",
                "message" => "Unauthorized"
            ],403);
        }
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|integer',
        ]);

        $checkRole = Role::find($validatedData["role_id"]);
        if(!$checkRole){
            return response()->json([
               "status" => "ERROR",
               "message" => "Role doesnt exist"
            ],404);
        }

        $user = User::where("email",$validatedData["email"])->first();

        if(!$user){
            $user = new User();
            $user->email =  $validatedData["email"];
        }
        $user->name =  $validatedData["name"];
        $user->role_id =  $validatedData["role_id"];
        $user->password =  Hash::make($validatedData["password"]);
        $user->save();


        return response()->json([
           "status" => "OK",
           "message" => "User created successfully",
            "user" => $user
        ],201);
    }

    public function account(Request $request)
    {
        $check_token = $this->checkToken($request);
        if(!$check_token){
            return response()->json([
                "status" => "ERROR",
                "message" => "Unauthorized"
            ],403);
        }
        $validatedData = $request->validate([
            'user_id' => 'required|integer',
            'amount' => 'required|integer',
        ]);
//        dd($validatedData);

        $user = User::with("account")->find($validatedData["user_id"]);

        if(!$user){
            return response()->json([
                "status" => "ERROR",
                "message" => "User doesnt exist"
            ],403);
        }

        $account = $user->account ?: new Account();

        $account->balance = $validatedData["amount"];
        $account->user_id = $user->id;
        $account->save();

        return response()->json([
           "status" => "OK",
           "message" => "Account created successfully",
            "account" => $account
        ],201);
    }

    public function createPermission(Request $request)
    {
        $check_token = $this->checkToken($request);
        if(!$check_token){
            return response()->json([
                "status" => "ERROR",
                "message" => "Unauthorized"
            ],403);
        }
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);
//        dd($validatedData);

        $permission = Permission::where("name",$validatedData["name"])->first();

        if(!$permission){
            $permission = new Permission();
        }
        $permission->name = $validatedData["name"];
        $permission->save();

        return response()->json([
           "status" => "OK",
           "message" => "Permission created successfully",
            "permission" => $permission
        ],201);
    }
    public function createRole(Request $request)
    {
        $check_token = $this->checkToken($request);
        if(!$check_token){
            return response()->json([
                "status" => "ERROR",
                "message" => "Unauthorized"
            ],403);
        }
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $role = Role::where("name",$validatedData["name"])->first();

        if(!$role){
            $role = new Role();
        }
        $role->name = $validatedData["name"];
        $role->save();

        return response()->json([
           "status" => "OK",
           "message" => "Role created successfully",
            "role" => $role
        ],201);
    }
    public function assignRolePermissions(Request $request)
    {
        $check_token = $this->checkToken($request);
        if(!$check_token){
            return response()->json([
                "status" => "ERROR",
                "message" => "Unauthorized"
            ],403);
        }
        $validatedData = $request->validate([
            'role_id' => 'required|integer',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::find($validatedData["role_id"]);

        if(!$role){
            return response()->json([
               "status" => "ERROR",
               "message" => "Role not found"
            ],404);
        }

        $exist_permission_ids = Permission::whereIn("id",$validatedData["permissions"])->pluck("id")->toArray();
//        dd($exist_permission_ids);
//        dd($role);
        $role->permissions()->sync($exist_permission_ids);

        return response()->json([
           "status" => "OK",
           "message" => "Permissions updated successfully",
        ],202);
    }
    public function createBanknote(Request $request)
    {
        $check_token = $this->checkToken($request);
        if(!$check_token){
            return response()->json([
                "status" => "ERROR",
                "message" => "Unauthorized"
            ],403);
        }
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
//            'value' => 'required|integer|max:200',
            'quantity' => 'required|integer',
        ]);

        $validBanknotes = [
          "teklik" => 1,
          "beshlik" => 5,
          "onluq" => 10,
          "iyirmilik" => 20,
          "ellilik" => 50,
          "yuzluk" => 100,
          "ikiyuzluk" => 200,
        ];

        if(!isset($validBanknotes[$validatedData["name"]])){
            return response()->json([
                "status"  => "ERROR",
                "message" => "Banknote name is not valid"
            ],403);
        }

        $banknote = Banknote::where("name",$validatedData["name"])->first();

        if(!$banknote){
            $banknote = new Banknote();
        }
        $banknote->name = $validatedData["name"];
        $banknote->value = $validBanknotes[$validatedData["name"]];
        $banknote->quantity = $validatedData["quantity"];
        $banknote->save();

        return response()->json([
           "status" => "OK",
           "message" => "Banknote created successfully",
            "banknote" => $banknote
        ],201);
    }

     public function withdraw(Request $request)
     {
         $check_token = $this->checkToken($request);
        if(!$check_token){
            return response()->json([
                "status" => "ERROR",
                "message" => "Unauthorized"
            ],403);
        }
        $validatedData = $request->validate([
            'user_id' => 'required|integer',
//            'value' => 'required|integer|max:200',
            'amount' => 'required|integer',
        ]);

        $user = User::with(["account","role","role.permissions"])->find($validatedData["user_id"]);

        $access = $user->role->permissions->where("name","withdraw")->first();

        if(!$access){
            return response()->json([
               "status" => "ERROR",
               "message" => "You dont have access to execute this operation"
            ],403);
        }

        if($validatedData["amount"] > $user->account->balance){
            return response()->json([
               "status" => "ERROR",
               "message" => "User balance not enough"
            ],403);
        }

        $available_banknotes = Banknote::where("quantity",">",0)->orderBy("value","DESC")->get();

        $total_amount_in_atm = 0;


        foreach ($available_banknotes as $banknote){
            $sum = $banknote->value * $banknote->quantity;
            $total_amount_in_atm += $sum;
        }

        if($validatedData["amount"] > $total_amount_in_atm){
            return response()->json([
               "status" => "ERROR",
               "message" => "Not enough amount in ATM"
            ],403);
        }

//        dd($total_amount_in_atm);

        $get_banknotes = $this->defineBanknotes($validatedData["amount"],$available_banknotes);

//        return $get_banknotes;


        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->status = $get_banknotes["success"];
        $transaction->amount = $validatedData["amount"];
        $transaction->details = json_encode($get_banknotes);


        if($get_banknotes["success"]){
//            Banknote::update($get_banknotes["updating_list"]);
            foreach ($get_banknotes["updating_list"] as $updating_item){
                $bnknt = Banknote::find($updating_item["id"]);
                $bnknt->quantity = $updating_item["quantity"];
                $bnknt->save();
            }
            $user->account->balance -= $validatedData["amount"];
            $user->account->save();
        }

        $transaction->save();




        return response()->json([
           "status" => $get_banknotes["success"] ? "OK" : "ERROR",
           "message" => $get_banknotes["message"],
           "banknotes" => $get_banknotes["banknotes"],
            "transaction_id" => $transaction->id
        ],201);
     }

     private function defineBanknotes($amount,$available_banknotes){
//        dd($available_banknotes->toArray());
        $result = [];
        $updating_list = [];
        $details = [];
        foreach ($available_banknotes as $key => $banknote){
//             if($banknote["value"] == 50) dd($amount,$banknote->value,$banknote->quantity);
            if($amount >= $banknote->value){
                $needed_count = (int) ($amount / $banknote->value);
                if($banknote->quantity > 0){
                    $banknote_count = $banknote->quantity >= $needed_count ? $needed_count : $banknote->quantity;
                    $result[] = [
                        "banknote" => $banknote->name,
                        "value" => $banknote->value,
                        "count" => $banknote_count,
                    ];
                    $details[] = [
                        "id" => $banknote->id,
                        "banknote" => $banknote->name,
                        "value" => $banknote->value,
                        "count" => $banknote_count,
                        "previous_quantity_of_banknote" => $banknote->quantity,
                    ];
                    $updating_list[] = [
                      "id" => $banknote->id,
                      "quantity" => $banknote->quantity - $banknote_count,
                    ];
                    $amount = $amount -  $banknote->value * $banknote_count;
                }
            }
        }


        $res = [
            "success" => $amount == 0,
            "message" => $amount == 0 ? "Success" :"Not enough banknotes in ATM",
            "banknotes" => $amount == 0 ? $result : [],
            "details" =>  $details,
            "updating_list" =>  $updating_list
        ] ;

        return $res;
     }

     public function deleteWithdraw(Request $request){
        $check_token = $this->checkToken($request);
        if(!$check_token){
            return response()->json([
                "status" => "ERROR",
                "message" => "Unauthorized"
            ],403);
        }
        $validatedData = $request->validate([
            'transaction_id' => 'required|integer',
            'operator_id' => 'required|integer',
//            'value' => 'required|integer|max:200',
        ]);

        $operator = User::with(["role","role.permissions"])->find($validatedData["operator_id"]);

        $access = $operator->role->permissions->where("name","delete-withdraw")->first();

        if(!$access){
            return response()->json([
               "status" => "ERROR",
               "message" => "You dont have access to execute this operation"
            ],403);
        }


        $transaction = Transaction::with("user")->find($validatedData["transaction_id"]);

        if(!$transaction){
            return response()->json([
               "status" => "ERROR",
               "message" => "Transaction not found"
            ],404);
        }

        if($transaction->deleted_at){
            return response()->json([
               "status" => "ERROR",
               "message" => "Transaction already deleted"
            ],403);
        }

        if(!$transaction->status){
            return response()->json([
               "status" => "ERROR",
               "message" => "Transaction is unsuccessful"
            ],403);
        }

        $details = json_decode($transaction->details,true);
        $details = $details["details"];
        foreach ($details as $detail){
            $banknote = Banknote::find($detail["id"]);
            $banknote->quantity = $detail["previous_quantity_of_banknote"];
            $banknote->save();
        }
        $account = Account::where("user_id",$transaction->user_id)->first();
        $account->balance += $transaction->amount;
        $account->save();

        $transaction->deleted_at = Carbon::now();
        $transaction->save();

        return response()->json([
            "status" => "OK",
            "message" => "Transaction deleted successfully"
        ],202);

     }

     public function getUserAndAccounts(Request $request){
        $check_token = $this->checkToken($request);
        if(!$check_token){
            return response()->json([
                "status" => "ERROR",
                "message" => "Unauthorized"
            ],403);
        }
        return response()->json([
            "status" => "OK",
            "data" => User::with("account","role","role.permissions")->get()->toArray()
        ]);
     }


     public function getRoleAndPermissions(Request $request){
        $check_token = $this->checkToken($request);
        if(!$check_token){
            return response()->json([
                "status" => "ERROR",
                "message" => "Unauthorized"
            ],403);
        }
        $roles = Role::with("permissions")->get()->toArray();
        $permissions = Permission::all();
        return response()->json([
            "status" => "OK",
            "permissions" => $permissions,
            "roles" => $roles
        ]);
     }

     public function getBanknotes(Request $request){
        $check_token = $this->checkToken($request);
        if(!$check_token){
            return response()->json([
                "status" => "ERROR",
                "message" => "Unauthorized"
            ],403);
        }

        return response()->json([
            "status" => "OK",
            "data" => Banknote::when($request->get("left_quantity",null),function($query,$left_quantity){
                return $query->where("quantity",">=",$left_quantity);
            })->orderBy("value","DESC")->get()->toArray()
        ]);
     }
     public function getTransactions(Request $request){
        $check_token = $this->checkToken($request);
        if(!$check_token){
            return response()->json([
                "status" => "ERROR",
                "message" => "Unauthorized"
            ],403);
        }

        $transactions = Transaction::when($request->get("user_id",null),function($query,$user_id){
                return $query->where("user_id",$user_id);
            })->with("user")->whereNull("deleted_at")->get();
        foreach ($transactions as $transaction){
            $transaction->details = $transaction->details ? json_decode($transaction->details,true) : [];
        }
        return response()->json([
            "status" => "OK",
            "data" => $transactions->toArray()
        ]);
     }

     private function checkToken(Request $request){
        $token = $request->header("api-key");
        return $token == env("API_KEY");
     }


}
