<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserAppointment;
use App\Models\UserFavourite;
use App\Models\Barber;
use App\Models\BarberAvailability;
use App\Models\BarberPhoto;
use App\Models\BarberService;
use App\Models\BarberTestimonial;

class BarberController extends Controller
{
    private $loggedUser;
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }
    public function createRandom() {
        $array = ['error' => ''];
        for ($q = 0; $q < 15; $q++) {
            $names = ['Boniek', 'Paulo', 'Pedro', 'Amanda', 'Leticia', 'Gabriel', 'Gabriela', 'Thais', 'Luiz', 'Diogo', 'José', 'Jeremias', 'Francisco', 'Dirce', 'Marcelo'];
            $lastnames = ['Santos', 'Silva', 'Santos', 'Silva', 'Alvaro', 'Sousa', 'Diniz', 'Josefa', 'Luiz', 'Diogo', 'Limoeiro', 'Santos', 'Limiro', 'Nazare', 'Mimoza'];
            $servicos = ['Corte', 'Pintura', 'Aparação', 'Unha', 'Progressiva', 'Limpeza de Pele', 'Corte Feminino'];
            $servicos2 = ['Cabelo', 'Unha', 'Pernas', 'Pernas', 'Progressiva', 'Limpeza de Pele', 'Corte Feminino'];
            $depos = [
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.'
            ];
            $newBarber = new Barber();
            $newBarber->name = $names[rand(0, count($names) - 1)] . ' ' . $lastnames[rand(0, count($lastnames) - 1)];
            $newBarber->avatar = rand(1, 4) . '.png';
            $newBarber->stars = rand(2, 4) . '.' . rand(0, 9);
            $newBarber->latitude = '-23.5' . rand(0, 9) . '30907';
            $newBarber->longitude = '-46.6' . rand(0, 9) . '82759';
            $newBarber->save();
            $ns = rand(3, 6);
            for ($w = 0; $w < 4; $w++) {
                $newBarberPhoto = new BarberPhoto();
                $newBarberPhoto->id_barber = $newBarber->id;
                $newBarberPhoto->url = rand(1, 5) . '.png';
                $newBarberPhoto->save();
            }
            for ($w = 0; $w < $ns; $w++) {
                $newBarberService = new BarberService();
                $newBarberService->id_barber = $newBarber->id;
                $newBarberService->name = $servicos[rand(0, count($servicos) - 1)] . ' de ' . $servicos2[rand(0, count($servicos2) - 1)];
                $newBarberService->price = rand(1, 99) . '.' . rand(0, 100);
                $newBarberService->save();
            }
            for ($w = 0; $w < 3; $w++) {
                $newBarberTestimonial = new BarberTestimonial();
                $newBarberTestimonial->id_barber = $newBarber->id;
                $newBarberTestimonial->name = $names[rand(0, count($names) - 1)];
                $newBarberTestimonial->rate = rand(2, 4) . '.' . rand(0, 9);
                $newBarberTestimonial->body = $depos[rand(0, count($depos) - 1)];
                $newBarberTestimonial->save();
            }
            for ($e = 0; $e < 4; $e++) {
                $rAdd = rand(7, 10);
                $hours = [];
                for ($r = 0; $r < 8; $r++) {
                    $time = $r + $rAdd;
                    if ($time < 10) {
                        $time = '0' . $time;
                    }
                    $hours[] = $time . ':00';
                }
                $newBarberAvail = new BarberAvailability();
                $newBarberAvail->id_barber = $newBarber->id;
                $newBarberAvail->weekday = $e;
                $newBarberAvail->hours = implode(',', $hours);
                $newBarberAvail->save();
            }
        }
        return $array;
    
    }

    public function list(Request $request) {
        $array = ['error' => ''];

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $city = $request->input('city');
        $offset = $request->input('offset');

        if(!$offset) {
            $offset = 0;
        }

        if(!empty($city)) {
            $res = $this->searchGeo($city);

            if(count($res['result']) > 0) {
                $lat = $res['results'][0]['geometry']['location']['lat'];
                $lng = $res['results'][0]['geometry']['location']['lng'];
            }
        } else if (!empty($lat) && !empty($lng)) {
            $res = $this->searchGeo($lat.','.$lng);

            if(count($res['result']) > 0) {
                $city = $res['results'][0]['formatted_address'];
            }
        } else {
            $lat = '-23.5630907';
            $lng = '-46.6682795';
            $city = 'São Paulo';
        }

        $barbers = Barber::select(Barber::raw('*, SQRT(
            POW(69.1 * (latitude - '.$lat.'), 2) +
            POW(69.1 * ('.$lng.' - longitude) * COS(latitude / 57.3), 2)) AS distance'))
            ->havingRaw('distance < ?', [10])
            ->orderBy('distance', 'ASC')
            ->offset($offset)
            ->limit(5)
            ->get();

        foreach($barbers as $key => $info) {
            $barbers[ $key ]['avatar'] = url('media/avatars/'.$barbers[ $key ]['avatar']);
        }

        $array['data'] = $barbers;
        $array['loc'] = 'São Paulo';

        return $array;
    }

    private function searchGeo($address) {
        $key = env('MAPS_KEY', null);

        $address = url_encode($address);

        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&key='.$key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }

    public function one($id) {
        $array = ['error' => ''];

        $barber = Barber::find($id);

        if($barber) {
            $barber['avatar'] = url('media/avatars/'.$barber['avatar']);
            $barber['favourited'] = false;
            $barber['photos'] = [];
            $barber['services'] = [];
            $barber['testimonials'] = [];
            $barber['availability'] = [];

            // Favorito
            $countFavourite = UserFavourite::where('id_user', $this->loggedUser->id)
                                ->where('id_barber', $barber->id)
                                ->count();
            
            $barber['favourited'] = $countFavourite > 0 ? true : false;

            // Fotos
            $barber['photos'] = BarberPhoto::select(['id', 'url'])->where('id_barber', $barber->id)->get();
            foreach($barber['photos'] as $key => $value) {
                $barber['photos'][ $key ]['url'] = url('media/uploads/'.$barber['photos'][ $key ]['url']);
            }

            // Serviços
            $barber['services'] = BarberService::select(['id', 'name', 'price'])->where('id_barber', $barber->id)->get();

            // Depoimentos
            $barber['testimonials'] = BarberTestimonial::select(['id', 'name', 'rate', 'body'])->where('id_barber', $barber->id)->get();

            // Disponibilidade
            $availability = [];

            $avails = BarberAvailability::where('id_barber', $barber->id)->get();
            $availWeekedays = [];
            foreach($avails as $item) {
                $availWeekdays[$item['weekday']] = explode(',', $item['hours']);
            }
            // Agendamentos dos próximos 20 dias
            $appointments = [];
            $appQuery = UserAppointment::where('id_barber', $barber->id)
                            ->whereBetween('ap_datetime', [
                                date('Y-m-d').' 00:00:00',
                                date('Y-m-d', strtotime('+20 days')).' 23:59:59'
                            ])
                            ->get();
            foreach($appQuery as $appItem) {
                $appointments[] = $appItem['ap_datetime'];
            }

            // Gerar disponibilidade real
            for($q=0;$q<20;$q++) {
                $timeItem = strtotime('+'.$q.' days');
                $weekday = date('w', $timeItem);

                if(in_array($weekday, array_keys($availWeekdays))) {
                    $hours = [];

                    $dayItem = date('Y-m-d', $timeItem);

                    foreach($availWeekdays[$weekday] as $hourItem) {
                        $dayFormatted = $dayItem . ' ' . $hourItem . ':00';
                        if(!in_array($dayFormatted, $appointments)) {
                            $hours[] = $hourItem;
                        }
                    }

                    if(count($hours) > 0) {
                        $availability[] = [
                            'date' => $dayItem,
                            'hours' => $hours
                        ];
                    }
                }
            }

            $barber['availability'] = $availability;

            $array['data'] = $barber;
        } else {
            $array['error'] = 'Barbeiro não encontrado.';
        }

        return $array;
    }

    public function setAppointment($id, Request $request) {
        $array = ['error' => ''];

        $service = $request->input('service');
        $year = intval($request->input('year'));
        $month = intval($request->input('month'));
        $day = intval($request->input('day'));
        $hour = $request->input('hour');

        $month = ($month < 10) ? '0'.$month : $month;
        $day = ($day < 10) ? '0'.$day : $day;
        $hour = ($hour < 10) ? '0'.$hour : $hour;

        // 1. verificar se o serviço do barbeiro existe
        $barberService = BarberService::select()
            ->where('id', $service)
            ->where('id_barber', $id)
            ->first();
        
        if(!$barberService) {
            $array['error'] = 'Serviço inexistente!';
            return $array;
        }

        // 2. verificar se a data é real
        $apDate = $year . '-' . $month . '-' . $day . ' ' . $hour . ':00:00';
        
        if(strtotime($apDate) <= 0) {
            $array['error'] = 'Data inválida!';
            return $array;
        }

        // 3. verificar se barbeiro já possui agendamento
        $appsCount = UserAppointment::select()
                ->where('id_barber', $id)
                ->where('ap_datetime', $apDate)
                ->count();

        if($appsCount !== 0) {
            $array['error'] = 'Já possui agendamento neste dia/hora';
            return $array;
        }

        // 4. verificar se barbeiro atende nesta data/hora
        // 4.1 dia
        $weekday = date('w', strtotime($apDate));
        $avail = BarberAvailability::select()
                    ->where('id_barber', $id)
                    ->where('weekday', $weekday)
                    ->first();
        
        if(!$avail) {
            $array['error'] = 'Barbeiro não atende neste dia.';
            return $array;
        }

        // 4.2 hora
        $hours = explode(',', $avail['hours']);
        
        if(!in_array($hour.':00', $hours)) {
            $array['error'] = 'Barbeiro não atende neste horário.';
            return $array;
        }

        // 5. fazer o agendamento
        $newApp = new UserAppointment();
        $newApp->id_user = $this->loggedUser->id;
        $newApp->id_barber = $id;
        $newApp->id_service = $service;
        $newApp->ap_datetime = $apDate;
        $newApp->save();

        return $array;
    }

    public function search(Request $request) {
        $array = ['error' => '', 'list' => []];

        $q = $request->input('q');

        $barbers = Barber::select()
            ->where('name', 'LIKE', '%'.$q.'%')
            ->get();

        foreach($barbers as $key => $info) {
            $barbers[ $key ]['avatar'] = url('media/avatars/'.$barbers[ $key ]['avatar']);
        }
        
        $array['list'] = $barbers;

        return $array;
    }
}
