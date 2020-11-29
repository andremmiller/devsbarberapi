<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserFavourite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\UserAppointment;
use App\Models\Barber;
use App\Models\User;
use App\Models\BarberService;
use Intervention\Image\Facades\Image;

class UserController extends Controller
{
    private $loggedUser;

    public function __construct() {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function read() {
        $array = ['error' => ''];

        $info = $this->loggedUser;
        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $array['data'] = $info;

        return $array;
    }

    public function toggleFavourite(Request $request) {
        $array = ['error' => ''];

        $barber = intval($request->input('barber'));

        if(!$barber) {
            $array['error'] = 'Barbeiro não informado';
            return $array;
        }

        $fav = UserFavourite::select()
            ->where('id_user', $this->loggedUser->id)
            ->where('id_barber', $barber)
            ->first();
        
            if(!$fav) {
                $newFav = new UserFavourite();
                $newFav->id_user = $this->loggedUser->id;
                $newFav->id_barber = $barber;
                $newFav->save();
                $array['have'] = true;
            } else {
                $fav->delete();
                $array['have'] = false;
            }

        return $array;
    }

    public function getFavourites() {
        $array = ['error' => '', 'list' => []];

        $favs = DB::table('userfavourites')
            ->join('barbers', 'userfavourites.id_barber', '=', 'barbers.id')
            ->select('barbers.*')
            ->where('userfavourites.id_user', '=', $this->loggedUser->id)
            ->get();
        
        $array['list'] = $favs;

        return $array;
    }

    public function getAppointments() {
        $array = ['error' => '', 'list' => []];

        $apps = UserAppointment::select()
            ->where('id_user', $this->loggedUser->id)
            ->orderBy('ap_datetime', 'DESC')
            ->get();
        
            if($apps) {
                foreach($apps as $app) {
                    $barber = Barber::find($app['id_barber']);
                    $barber['avatar'] = url('media/avatars/'.$barber['avatar']);

                    $service = BarberService::find($app['id_service']);

                    $array['list'][] = [
                        'id' => $app['id'],
                        'datetime' => $app['ap_datetime'],
                        'barber' => $barber,
                        'service' => $service
                    ];
                }
            }

        return $array;
    }

    public function update(Request $request) {
        $array = ['error' => ''];

        $rules = [
            'name' => 'min:2',
            'email' => 'email|unique:users',
            'password' => 'same:password_confirm',
            'password_confirm' => 'same:password'
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $array['error'] = $validator->messages();
            return $array;
        }

        $user = User::find($this->loggedUser->id);

        if($request->input('name')) {
            $user->name = $request->input('name');
        }

        if($request->input('email')) {
            $user->email = $request->input('email');
        }

        if($request->input('password')) {
            $user->password = password_hash($request->input('password'));
        }

        $user->save();
        

        return $array;
    }

    public function updateAvatar(Request $request) {
        $array = ['error' => ''];

        $rules = [
            'avatar' => 'required|image|mimes:png,jpg,jpeg'
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $array['error'] = $validator->messages();
            return $array;
        }

        $avatar = $request->file('avatar');

        $dest = public_path('/media/avatars');
        $avatarName = md5(time().rand(0, 9999)).'.jpg';

        $img = Image::make($avatar->getRealPath());
        $img->fit(300, 300)->save($dest.'/'.$avatarName);

        $user = User::find($this->loggedUser->id);
        $user->avatar = $avatarName;
        $user->save();

        return $array;
    }
}
