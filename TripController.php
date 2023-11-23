<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TripController extends Controller
{
    protected $user;

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index()
    {
        $userId = Auth::id();

        $trips = DB::table('trips')
            ->where('user_id', '=', $userId)
            ->where('status', '!=', Trip::DELETED)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = [
            'trips' => $trips
        ];

        return view('trips.index', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $geocode = $request->input('geocode');
        $user_id = $request->input('user_id');
        $ref_id = $request->input('ref_id');
        $tripName = $request->input('tripName');
        $distance = $request->input('distance');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $images = $request->file('images');

        $validator = Validator::make($request->all(), [
            'geocode' => ['required'],
            'user_id' => ['required'],
            'ref_id' => ['required'],
            'tripName' => ['required'],
            'distance' => ['required'],
            'startDate' => ['required','date'],
            'endDate' => ['required','date']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'Result' => false,
                'error' => $validator->errors()
            ]);
        } else {
            $tripPrev = Trip::where('ref_id', $ref_id)
                        ->where('user_id', null)
                        ->get();

            if (count($tripPrev)) {
                $tripPrev->each->delete();
            }

            $refAvailability = Trip::where('ref_id', $ref_id)->where('status', '!=', Trip::DELETED)->first();

            if ($refAvailability) {
                return response()->json([
                    'Result' => false,
                    'error' => 'Ref Id already exists.'
                ]);
            } else {
                $user = User::find($user_id);

                if ($user) {
                    $trip = Trip::create([
                        'geocode' => $geocode,
                        'user_id' => $user_id,
                        'ref_id' => $ref_id,
                        'trip_name' => $tripName,
                        'distance' => $distance,
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]);

                    if (isset($trip->id)) { 
                        if ($request->hasFile('images')) {
                            $namesAr = [];
                            foreach ($images as $image) {
                              // $destinationPath = 'trips/'.$trip->id;
                              $filename = $image->getClientOriginalName();
                              // $image->move($destinationPath, $filename);
                              $image->storeAs('public/trips/'.$trip->id, $filename);
                              array_push($namesAr, $filename);
                            }
                            $namesArJson = json_encode($namesAr);

                            $trip->images = $namesArJson;
                            $trip->save();
                        }

                        $encTripId = Crypt::encrypt($trip->id);

                        return response()->json([
                            'Result' => true,
                            'Message' => 'Recorded successfully',
                            'TripID' => $trip->id
                        ]);
                    } else {
                        return response()->json([
                            'Result' => false,
                            'error' => 'Unknown error'
                        ]);
                    }
                } else {
                    return response()->json([
                        'Result' => false,
                        'error' => 'Invalid user'
                    ]);
                }
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Trip $trip)
    {
        $tripId = $trip->id;
        $geocode = $trip->geocode;
        $imagesAr = $trip->images;
        $tripName = $trip->trip_name;

        $locArray = explode('/', $geocode);


        $distance = $trip->distance;
        $startDate = $trip->start_date;
        $endDate = $trip->end_date;

        if (!empty($distance) && !empty($startDate) && !empty($endDate)) {
            $distanceKm = $distance/1000;
            $distanceKmFmt = number_format((float)$distanceKm, 2, '.', '');

            $distanceMi = $distance*0.000621371192;
            $distanceMiFmt = number_format((float)$distanceMi, 2, '.', '');

            $start  = new Carbon($startDate);
            $end    = new Carbon($endDate);

            $duration = $start->diffInHours($end) . ':' . $start->diff($end)->format('%I:%S');
        }

        $data = [
            'lastTrip' => $locArray,
            'imagesAr' => $imagesAr ? $imagesAr : 0,
            'tripId' => $tripId,
            'tripName' => $tripName,
            'distanceKm' => isset($distanceKmFmt) ? $distanceKmFmt : null,
            'distanceMile' => isset($distanceMiFmt) ? $distanceMiFmt : null,
            'duration' => isset($duration) ? $duration : null
        ];

        return view('trips.view', $data);
    }

    /**
     * Update the specified resource.
     * @return \Illuminate\Http\Response
     */
    public function updateTripData(Request $request)
    {
        $formData = $request->input('formData');
        $id = $formData[0]['value'];
        $tripName = $formData[1]['value'];

        $trip = Trip::where('id', $id)->first();

        if (isset($trip->id)) {
            $affected = DB::table('trips')
              ->where('id', $id)
              ->update(['trip_name' => $tripName]);

            return response()->json(true);
        } else {
            return response()->json(false);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function getTripData(Request $request)
    {
        $id = $request->input('tId');

        $trip = Trip::where('id', $id)->first();

        $tripName = $trip->trip_name;

        return response()->json([
            'tripId' => $id,
            'tripName' => $tripName
        ]);
    }

    /**
     * Update the specified resource.
     * @return \Illuminate\Http\Response
     */
    public function deleteTripData(Request $request)
    {
        $id = $request->input('tId');

        $trip = Trip::where('id', $id)->first();

        if (isset($trip->id)) {
            $affected = DB::table('trips')
              ->where('id', $id)
              ->update(['status' => Trip::DELETED]);

            $directory = 'public/trips/'.$trip->id;
            if (Storage::exists($directory)) {
                Storage::deleteDirectory($directory);
            }

            return response()->json(true);
        } else {
            return response()->json(false);
        }
    }
}
