<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class StreamController extends Controller
{

   
    private string $apiKey;
    private string $apiSecret;
    private string $videoBase;

    public function __construct()
    {
        $this->apiKey    = (string) config('services.stream.key', env('STREAM_API_KEY'));
        $this->apiSecret = (string) config('services.stream.secret', env('STREAM_API_SECRET'));
        $this->videoBase = rtrim((string) env('STREAM_VIDEO_BASE', 'https://video.stream-io-api.com/api/v2'), '/');
    }

    
    public function issueUserToken(Request $request)
    {
        $user = Auth::user();

        $now = time();
        $exp = $now + 24 * 3600; 

        $payload = [
            'user_id' => (string) ($user->unique_user_id ?? $user->id), 
            'iat'     => $now,
            'exp'     => $exp,
        ];

       
        $token = JWT::encode($payload, $this->apiSecret, 'HS256');

        return response()->json([
            'api_key' => $this->apiKey,
            'token'   => $token,
            'user'    => [
                'id'   => (string) ($user->unique_user_id ?? $user->id),
                'name' => $user->name,
                'role' => optional($user->role)->name, 
            ],
        ]);
    }

   
    // public function upsertUsers(Request $request)
    // {
    //     $data = $request->all();

    //     Validator::make($data, [
    //         'users'           => ['required', 'array', 'min:1'],
    //         'users.*.id'      => ['required'],                 
    //         'users.*.name'    => ['nullable', 'string'],
    //         'users.*.image'   => ['nullable', 'string'],
    //         'users.*.role'    => ['nullable', 'string'],
    //         'users.*.custom'  => ['nullable', 'array'],
    //     ])->validate();

    //     // Stream expects: { users: { "<id>": { id, name, ... }, ... } }
    //     $usersMap = collect($data['users'])->mapWithKeys(static function (array $u) {
    //         $id = (string) $u['id'];

    //         return [$id => [
    //             'id'     => $id,
    //             'name'   => $u['name']  ?? null,
    //             'image'  => $u['image'] ?? null,
    //             'role'   => $u['role']  ?? 'user',
    //             'custom' => $u['custom'] ?? null,
    //         ]];
    //     })->toArray();

    //     // Server JWT (no user_id) for Stream REST calls
    //     $serverToken = $this->makeServerToken();

    //     $resp = Http::withHeaders([
    //         'Authorization'    => $serverToken,
    //         'stream-auth-type' => 'jwt',
    //         'Content-Type'     => 'application/json',
    //     ])
    //         ->post($this->videoBase . "/users?api_key={$this->apiKey}", ['users' => $usersMap]);

    //     return response()->json($resp->json(), $resp->status());
    // }

    public function upsertUsers(Request $request)
    {
       
        $data = $request->all();

        

        if (array_key_exists('users', $data) && is_string($data['users'])) {
            $decoded = json_decode($data['users'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $data['users'] = $decoded;
            } else {
                return response()->json([
                    'error'   => 'Invalid JSON in "users". Use valid JSON (double-quoted keys/values).',
                    'example' => '[{"id":"123","name":"Alice","role":"doctor"}]'
                ], 422);
            }
        }

       
        $payload = Validator::make($data, [
            'users'           => ['required', 'array', 'min:1'],
            'users.*.id'      => ['required'],
            'users.*.name'    => ['nullable', 'string'],
            'users.*.image'   => ['nullable', 'string'],
            'users.*.role'    => ['nullable', 'string'],
            'users.*.custom'  => ['nullable', 'array'],
        ])->validate();

        $users = $payload['users'];

       
        $usersMap = collect($users)->mapWithKeys(static function (array $u) {
            $id = (string) $u['id'];

            return [$id => [
                'id'     => $id,
                'name'   => $u['name']   ?? null,
                'image'  => $u['image']  ?? null,
                'role'   => $u['role']   ?? 'user',
                'custom' => $u['custom'] ?? null,
            ]];
        })->toArray();

        
        $serverToken = $this->makeServerToken();

        $resp = Http::withHeaders([
            'Authorization'    => $serverToken,
            'stream-auth-type' => 'jwt',
            'Content-Type'     => 'application/json',
        ])
            ->post($this->videoBase . "/users?api_key={$this->apiKey}", ['users' => $usersMap]);

        return response()->json($resp->json(), $resp->status());
    }


    public function startCall(Request $request, int $appointmentId)
    {
        $authUser = Auth::user();


       
        if (! method_exists($authUser, 'isProvider') || ! $authUser->isProvider()) {
            return response()->json(['error' => 'Only providers can start calls.'], 403);
        }

       
        $appointment = Appointment::with(['customer.role', 'provider.role'])->findOrFail($appointmentId);

       
        if ((int) $appointment->provider_id !== (int) $authUser->id) {
            return response()->json(['error' => 'This appointment does not belong to you.'], 403);
        }

       
        if (! $appointment->provider || ! $appointment->provider->isProvider()) {
            return response()->json(['error' => 'Only provider appointments can start calls.'], 403);
        }

      
        if ($appointment->status !== 'confirmed') {
            return response()->json(['error' => 'Appointment must be confirmed.'], 422);
        }

        
        // $when = Carbon::createFromFormat('Y-m-d H:i:s', $appointment->getRawOriginal('appointment_time'), 'UTC');
        // $nowUtc = Carbon::now('UTC');
       

        // if ($nowUtc->lt($when)) {
        //     return response()->json(['error' => 'You can start the call only at or after the appointment time.'], 422);
        // }

        // Block if the window ends

        // $slotMinutes = $this->resolveSlotDurationForAppointment(
        //     providerId: (int) $appointment->provider_id,
        //     appointmentStartUtc: $when
        // );

        // if ($slotMinutes === null) {
           
        //     return response()->json(['error' => 'No matching provider availability for this appointment time.'], 422);
           
        // }

      
        // $slotEnd = $when->copy()->addMinutes($slotMinutes); 
        // if ($nowUtc->gt($slotEnd)) {
        //     return response()->json(['error' => 'Call window has ended for this appointment.'], 422);
        // }

       
        $this->upsertUsers(new Request([
            'users' => [
                [
                    'id'   => (string) ($appointment->provider->unique_user_id ?? $appointment->provider->id),
                    'name' => $appointment->provider->name,
                    'role' => optional($appointment->provider->role)->name ?? 'provider',
                ],
                [
                    'id'   => (string) ($appointment->customer->unique_user_id ?? $appointment->customer->id),
                    'name' => $appointment->customer->name,
                    'role' => optional($appointment->customer->role)->name ?? 'customer',
                ],
            ],
        ]));

        
        $callCid = "default:appt-{$appointment->id}";

        
        return response()->json([
            'call_cid'     => $callCid,
            'ring_payload' => [
                'callCid'       => $callCid,
                'providerId'      => (string) ($authUser->unique_user_id ?? $authUser->id),
                'providerName'    => $authUser->name,
                'appointmentId' => (int) $appointment->id,
            ],
        ], 200);
    }

    
    private function makeServerToken(): string
    {
        $now = time();

        $payload = [
            'iat'    => $now,
            'exp'    => $now + 3600, 
            'server' => true,
        ];

        return JWT::encode($payload, $this->apiSecret, 'HS256');
    }



    private function resolveSlotDurationForAppointment(int $providerId, Carbon $appointmentStartUtc): ?int
    {
        $day  = strtolower($appointmentStartUtc->format('l'));
        $time = $appointmentStartUtc->format('H:i:s');

        $row = DB::table('service_provider_availabilities')
            ->where('provider_id', $providerId)
            ->where('availability_type', 'appointment')
            ->where('day', $day)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->orderByDesc('start_time') 
            ->first();

        if (! $row) {
            return null;
        }

        $slot = (int) ($row->slot_duration ?? 0);
        return $slot > 0 ? $slot : null;
    }
}
