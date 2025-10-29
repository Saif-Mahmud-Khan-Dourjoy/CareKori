<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CommonProviderSpeciality;
use App\Models\DoctorSpeciality;
use App\Models\LawyerSpeciality;
use App\Models\Role;
use App\Models\ServiceProviderAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CommonController extends Controller
{
    public function UserCount()
    {
        $getters     = User::whereHas('role', function ($q) {
            $q->whereRaw('LOWER(name) = ?', ['customer']);
        })->count();

        $moderators  = User::whereHas('role', function ($q) {
            $q->whereRaw('LOWER(name) = ?', ['moderator']);
        })->count();


        $providers   = User::whereHas('role', function ($q) {
            $q->whereNotIn(DB::raw('LOWER(name)'), ['customer', 'super admin', 'super_admin', 'moderator']);
        })->count();

        $users = User::count();

        return response()->json([

            'getters'    => $getters,
            'providers'  => $providers,
            'moderators' => $moderators,
            'users'      => $users,
        ]);
    }

    public function serviceAndSubServiceCount()

    {
        $servicesCount = Role::whereNotIn(DB::raw('LOWER(name)'), ['customer', 'super admin', 'super_admin', 'moderator'])->count();
        $usedServicesCount = Role::whereNotIn(DB::raw('LOWER(name)'), [
            'customer',
            'super admin',
            'moderator',
        ])
            ->whereHas('users')
            ->count();


        $doctorSubservicesCatalog = DB::table('doctor_specialities')->count();
        $lawyerSubservicesCatalog = DB::table('lawyer_specialities')->count();
        $commonSubservicesCatalog = DB::table('common_provider_specialities')->count();

        $totalSubservicesCatalog = $doctorSubservicesCatalog
            + $lawyerSubservicesCatalog
            + $commonSubservicesCatalog;


        $doctorSubservicesUsed = DB::table('doctor_profiles')->distinct()->count('doctor_speciality_id');
        $lawyerSubservicesUsed = DB::table('lawyer_profiles')->distinct()->count('lawyer_speciality_id');
        $commonSubservicesUsed = DB::table('common_profiles')->distinct()->count('common_speciality_id');

        $totalSubservicesUsed = $doctorSubservicesUsed
            + $lawyerSubservicesUsed
            + $commonSubservicesUsed;

        $totalCount = $servicesCount + $totalSubservicesCatalog;
        $totalUsedCount = $usedServicesCount + $totalSubservicesUsed;



        return response()->json([
            'services_count' => $servicesCount,
            'used_services_count' => $usedServicesCount,
            'sub_services_count' => $totalSubservicesCatalog,
            'used_sub_services_count' => $totalSubservicesUsed,
            'total_count' => $totalCount,
            'total_used_count' => $totalUsedCount,
        ]);
    }

    public function revenueProfitSeries(Request $request)
    {

        $year = (int)($request->get('year') ?? now()->year); // e.g. 2025

        // --------------- Month-wise (for a single year) ---------------
        // 1) Revenue by month (appointments)
        $revByMonth = DB::table('appointments')
            ->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m, SUM(price) as revenue')
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                // Treat null as not money-back
                $q->whereNull('is_money_back')
                    ->orWhere('is_money_back', false)
                    ->orWhere('is_money_back', 0);
            })
            ->whereYear('created_at', $year)
            ->groupBy('y', 'm')
            ->pluck('revenue', 'm'); // [monthNum => revenue]

        // 2) Payments by month (provider_withdrawals)
        $payByMonth = DB::table('provider_withdrawals')
            ->selectRaw('YEAR(withdrawn_at) as y, MONTH(withdrawn_at) as m, SUM(amount) as payments')
            ->where('status', 'success')
            ->whereYear('withdrawn_at', $year)
            ->groupBy('y', 'm')
            ->pluck('payments', 'm'); // [monthNum => payments]

        // Build 12 months with zeros filled, and profit = revenue - payments
        $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $revenue = (float) ($revByMonth[$m] ?? 0);
            $payments = (float) ($payByMonth[$m] ?? 0);
            $monthly[] = [
                'label'   => $monthLabels[$m - 1],
                'revenue' => $revenue,
                'profit'  => max(0, $revenue - $payments), // if you want raw difference, remove max()
            ];
        }

        // --------------- Year-wise (across all years) ---------------
        // Revenue per year
        $revByYear = DB::table('appointments')
            ->selectRaw('YEAR(created_at) as y, SUM(price) as revenue')
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('is_money_back')
                    ->orWhere('is_money_back', false)
                    ->orWhere('is_money_back', 0);
            })
            ->groupBy('y')
            ->pluck('revenue', 'y'); // [year => revenue]

        // Payments per year
        $payByYear = DB::table('provider_withdrawals')
            ->selectRaw('YEAR(withdrawn_at) as y, SUM(amount) as payments')
            ->where('status', 'success')
            ->groupBy('y')
            ->pluck('payments', 'y'); // [year => payments]

        // Merge keys and build the yearly array (sorted ascending)
        $allYears = collect(array_unique(array_merge($revByYear->keys()->toArray(), $payByYear->keys()->toArray())))
            ->sort()
            ->values();

        $yearly = [];
        foreach ($allYears as $y) {
            $revenue  = (float) ($revByYear[$y] ?? 0);
            $payments = (float) ($payByYear[$y] ?? 0);
            $yearly[] = [
                'label'   => (string) $y,
                'revenue' => $revenue,
                'profit'  => max(0, $revenue - $payments),
            ];
        }

        // --------------- Response in your desired shapes ---------------
        return response()->json([
            'monthlyByYear' => [
                $year => $monthly,
            ],
            'yearly' => $yearly,
        ]);
    }




    public function gettersProvidersSeries(Request $request)
    {
        // If no year is sent, use the current year
        $year = (int) ($request->get('year') ?: now()->year);


        $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // =============== MONTHLY (for given/default year) ===============
        // getters (role = customer)
        $gettersByMonth = DB::table('users as u')
            ->join('roles as r', 'r.id', '=', 'u.role_id')
            ->whereRaw('LOWER(r.name) = ?', ['customer'])
            ->whereYear('u.created_at', $year)
            ->selectRaw('MONTH(u.created_at) as m, COUNT(*) as c')
            ->groupBy('m')
            ->pluck('c', 'm'); // [month => count]

        // providers (exclude customer, super admin, moderator)
        $providersByMonth = DB::table('users as u')
            ->join('roles as r', 'r.id', '=', 'u.role_id')
            ->whereNotIn(DB::raw('LOWER(r.name)'), ['customer', 'super admin', 'super_admin', 'moderator'])
            ->whereYear('u.created_at', $year)
            ->selectRaw('MONTH(u.created_at) as m, COUNT(*) as c')
            ->groupBy('m')
            ->pluck('c', 'm'); // [month => count]

        $monthlyRows = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyRows[] = [
                'label'     => $monthLabels[$m - 1],
                'getters'   => (int) ($gettersByMonth[$m] ?? 0),
                'providers' => (int) ($providersByMonth[$m] ?? 0),
            ];
        }

        $monthlyByYear = [
            $year => $monthlyRows,
        ];

        // =============== YEARLY (across ALL years) ===============
        $yearlyRaw = DB::table('users as u')
            ->join('roles as r', 'r.id', '=', 'u.role_id')
            ->selectRaw('YEAR(u.created_at) as y')
            ->selectRaw("SUM(CASE WHEN LOWER(r.name) = 'customer' THEN 1 ELSE 0 END) as getters")
            ->selectRaw("SUM(CASE WHEN LOWER(r.name) NOT IN ('customer','super admin','super_admin','moderator') THEN 1 ELSE 0 END) as providers")
            ->groupBy('y')
            ->orderBy('y')
            ->get();

        $yearly = $yearlyRaw->map(fn($row) => [
            'label'     => (string) $row->y,
            'getters'   => (int) $row->getters,
            'providers' => (int) $row->providers,
        ])->values();

        return response()->json([
            'monthlyByYear' => $monthlyByYear, // given year or current year
            'yearly'        => $yearly,        // all years available
        ]);
    }

    public function getPendingApprovalProviders()
    {
        // helper: pending = active_status is NULL or 0/false
        $pending = function ($q) {
            $q->whereNull('active_status')
                ->orWhere('active_status', 0)
                ->orWhere('active_status', false);
        };

        $users = User::query()
            ->select('*')
            ->with([
                'role',

                // Doctor profile + nested refs (only pending)
                'doctorProfile' => function ($q) use ($pending) {
                    $q->select('*')->where($pending)
                        ->with([
                            'doctorType',
                            'doctorSpeciality',
                            'doctorTitle',
                        ]);
                },

                // Lawyer profile + nested refs (only pending)
                'lawyerProfile' => function ($q) use ($pending) {
                    $q->select('*')->where($pending)
                        ->with([
                            'lawyerTitle',
                            'lawyerSpeciality',
                        ]);
                },

                // Common profile + nested refs (only pending)
                'commonProfile' => function ($q) use ($pending) {
                    $q->select('*')->where($pending)
                        ->with([
                            'commonSpeciality',
                            'uniqueIdentification'
                        ]);
                },
            ])
            // providers = NOT customer / moderator / super admin
            ->whereHas('role', function ($q) {
                $q->whereNotIn(DB::raw('LOWER(name)'), ['customer', 'moderator', 'super admin', 'super_admin']);
            })
            // must have a pending profile in its specific bucket
            ->where(function ($q) use ($pending) {
                $q->whereHas('doctorProfile', $pending)
                    ->orWhereHas('lawyerProfile', $pending)
                    ->orWhereHas('commonProfile', $pending);
            })

            ->get();

        // Normalize: keep ONLY the matching profile; remove nulls from others
        $providers = $users->map(function ($u) {
            // decide which profile exists (only one should be pending at a time)
            if ($u->doctorProfile) {
                $profileType = 'doctor';
                $profile = $u->doctorProfile;
            } elseif ($u->lawyerProfile) {
                $profileType = 'lawyer';
                $profile = $u->lawyerProfile;
            } elseif ($u->commonProfile) {
                $profileType = 'common';
                $profile = $u->commonProfile;
            } else {
                $profileType = 'none';
                $profile = null;
            }

            return [
                'id'           => $u->id,
                'name'         => $u->name,
                'email'        => $u->email,
                'unique_user_id' => $u->unique_user_id,
                'phone'        => $u->phone,
                'role'         => $u->role?->name,   // always include role name
                'profile_type' => $profileType,      // 'doctor' | 'lawyer' | 'common' | 'none'
                'profile'      => $profile,          // object with nested refs, or null
                'created_at'   => $u->created_at,
            ];
        })->values();

        return response()->json([
            'providers' => $providers,
        ]);
    }

    public function getAllProviders()
    {
        // helper: pending = active_status is NULL or 0/false
       

        $users = User::query()
            ->select('*')
            ->with([
                'role',
                'availability',

                // Doctor profile + nested refs (only pending)
                'doctorProfile' => function ($q)  {
                    $q->select('*')
                        ->with([
                            'doctorType',
                            'doctorSpeciality',
                            'doctorTitle',
                        ]);
                },

                // Lawyer profile + nested refs (only pending)
                'lawyerProfile' => function ($q)  {
                    $q->select('*')
                        ->with([
                            'lawyerTitle',
                            'lawyerSpeciality',
                        ]);
                },

                // Common profile + nested refs (only pending)
                'commonProfile' => function ($q)  {
                    $q->select('*')
                        ->with([
                            'commonSpeciality',
                            'uniqueIdentification'
                        ]);
                },
            ])
            // providers = NOT customer / moderator / super admin
            ->whereHas('role', function ($q) {
                $q->whereNotIn(DB::raw('LOWER(name)'), ['customer', 'moderator', 'super admin', 'super_admin']);
            })
            // must have a pending profile in its specific bucket
            // ->where(function ($q)  {
            //     $q->whereHas('doctorProfile')
            //         ->orWhereHas('lawyerProfile')
            //         ->orWhereHas('commonProfile');
            // })

            ->get();


            // return $users;

        // Normalize: keep ONLY the matching profile; remove nulls from others
        $providers = $users->map(function ($u) {
            // decide which profile exists (only one should be pending at a time)
            if ($u->doctorProfile) {
                $profileType = $u->role?->name;
                $profile = $u->doctorProfile;
            } elseif ($u->lawyerProfile) {
                $profileType =  $u->role?->name;
                $profile = $u->lawyerProfile;
            } elseif ($u->commonProfile) {
                $profileType = $u->role?->name;
                $profile = $u->commonProfile;
            } else {
                $profileType = 'none';
                $profile = null;
            }

            return [
                'id'           => $u->id,
                'name'         => $u->name,
                'email'        => $u->email,
                'unique_user_id' => $u->unique_user_id,
                'role_id'       => $u->role_id,
                'phone'        => $u->phone,
                'role'         => $u->role?->name,   // always include role name
                'profile_type' => $profileType,      // 'doctor' | 'lawyer' | 'common' | 'none'
                'profile'      => $profile,          // object with nested refs, or null
                'availabilities' => $u->availability,
                'created_at'   => $u->created_at,
            ];
        })->values();

        return response()->json([
            'providers' => $providers,
        ]);
    }

    public function providerRevenueBoard()
    {
        // Eligible appointments = not cancelled AND not money-back (NULL/0/false)
        $incomeSub = DB::table('appointments as a')
            ->select('a.provider_id', DB::raw('SUM(a.price) as total_income'))
            ->where('a.status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNotNull('a.is_money_back')
                    ->orWhere('a.is_money_back', 0)
                    ->orWhere('a.is_money_back', false);
            })
            ->groupBy('a.provider_id');

        // Successful withdrawals per provider
        $withdrawSub = DB::table('provider_withdrawals as w')
            ->select(
                'w.provider_id',
                DB::raw("SUM(CASE WHEN w.status = 'success' THEN w.amount ELSE 0 END) as total_received"),
                DB::raw("MAX(CASE WHEN w.status = 'success' THEN w.withdrawn_at ELSE NULL END) as last_withdraw_at")
            )
            ->groupBy('w.provider_id');

        // Base rows: ONLY providers (role filter) who have >=1 eligible appointment (INNER JOIN incomeSub)
        $rows = DB::table('users as u')
            ->join('roles as r', 'r.id', '=', 'u.role_id')
            ->joinSub($incomeSub, 'ai', 'ai.provider_id', '=', 'u.id')       // <<< inner join enforces at least one eligible appointment
            ->leftJoinSub($withdrawSub, 'pw', 'pw.provider_id', '=', 'u.id') // withdrawals can be missing

            // department (speciality): prefer doctor > lawyer > common
            ->leftJoin('doctor_profiles as dp', 'dp.user_id', '=', 'u.id')
            ->leftJoin('doctor_specialities as ds', 'ds.id', '=', 'dp.doctor_speciality_id')
            ->leftJoin('lawyer_profiles as lp', 'lp.user_id', '=', 'u.id')
            ->leftJoin('lawyer_specialities as ls', 'ls.id', '=', 'lp.lawyer_speciality_id')
            ->leftJoin('common_profiles as cp', 'cp.user_id', '=', 'u.id')
            ->leftJoin('common_provider_specialities as cs', 'cs.id', '=', 'cp.common_speciality_id')

            // providers = NOT customer / moderator / super admin
            ->whereNotIn(DB::raw('LOWER(r.name)'), ['customer', 'moderator', 'super admin', 'super_admin'])

            ->select([
                'u.id',
                'u.name',
                'u.phone',
                'r.name as occupation',
                DB::raw('COALESCE(ds.specialized_at , ls.specialized_at , cs.specialized_at ) as department'),
                DB::raw('ai.total_income as total_income'),
                DB::raw('COALESCE(pw.total_received, 0) as amount_received'),
                DB::raw('(ai.total_income - COALESCE(pw.total_received, 0)) as amount_due'),
                DB::raw('pw.last_withdraw_at as last_trx_date'),
            ])
            ->orderByDesc('u.id')
            ->get()
            ->map(function ($r) {
                return [
                    'id'              => (int) $r->id,
                    'name'            => $r->name,
                    'phone'           => $r->phone,
                    'occupation'      => $r->occupation,
                    'department'      => $r->department,
                    'total_income'    => (float) $r->total_income,
                    'amount_due'      => (float) $r->amount_due,
                    'amount_received' => (float) $r->amount_received,
                    'last_trx_date'   => $r->last_trx_date,
                ];
            })->values();

        // Summary cards (computed from filtered rows)
        $summary = [
            'total_earnings'  => (float) $rows->sum('total_income'),
            'pending_due'     => (float) $rows->sum('amount_due'),
            'total_collected' => (float) $rows->sum('amount_received'),
        ];

        return response()->json([
            'summary'   => $summary,
            'providers' => $rows,
        ]);
    }

    public function deleteProvider($uniqueUserId)
    {
        $user = User::where('unique_user_id', $uniqueUserId)->first();

        if (!$user) {
            return response()->json(['message' => 'Provider not found.'], 404);
        }

        // Delete the user
        $user->delete();

        return response()->json(['message' => 'Provider deleted successfully.'], 200);
    }

    public function getAllUsers()
    {
        $users = User::withWhereHas('role', function ($query) {
            $query->whereNotIn(DB::raw('LOWER(name)'), ['super admin', 'moderator']);
        })->get();

        return response()->json([
            'users' => $users
        ]);
    }


    public function getProviderRolesWithSpecialities()
    {
        $roles = Role::whereNotIn('name', ['super admin', 'moderator', 'customer'])->get();

        $result = [];

        foreach ($roles as $role) {
            switch (strtolower($role->name)) {
                case 'doctor':
                    $specialities = DoctorSpeciality::select('id as value', 'specialized_at as label')->get();
                    break;
                case 'lawyer':
                    $specialities = LawyerSpeciality::select('id as value', 'specialized_at as label')->get();
                    break;
                default:
                    $specialities = CommonProviderSpeciality::select('id as value', 'specialized_at as label')->where('category_id', $role->id)->get();
                    break;
            }
            $result[$role->id] = $specialities;
        }

        return response()->json(['roleSpecialities' => $result]);
    }

    public function getAllCustomer(){
        $customers = User::with([
            'role',
            'customerProfile'
        ])->whereHas('role', function ($query) {
            $query->whereRaw('LOWER(name) = ?', ['customer']);
        })->get();

        return response()->json([
            'customers' => $customers
        ]);
    }

    public function getProviderRoles()
    {
        $roles = Role::select('id', 'name')->whereNotIn(DB::raw('LOWER(name)'), ['customer', 'moderator', 'super admin'])->get();

        return response()->json([
            'roles' => $roles
        ]);
    }

    public function doctorUpdate(Request $request, $uniqueUserId)
    {
        $user = User::where('unique_user_id', $uniqueUserId)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Validate the incoming request
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'doctor_type_id' => 'sometimes|required|exists:doctor_types,id',
            'doctor_speciality_id' => 'sometimes|required|exists:doctor_specialities,id',
            'doctor_title_id' => 'sometimes|required|exists:doctor_titles,id',
            'bio' => 'nullable|string',
            'pricing' => 'nullable|numeric|min:0',
            'district' => 'nullable|string|max:255',
            'thana' => 'nullable|string|max:255',
            'identification_no' => 'sometimes|required|string|max:255',
            'registration_no' => 'sometimes|required|string|max:255',
            'active_from' => 'nullable|date_format:H:i',
            'active_to' => 'nullable|date_format:H:i|after:active_from',
            'address' => 'sometimes|nullable|string|max:500',
            'avatar' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,gif|max:2048', // Avatar validation
            'bank_name' => 'nullable|string|max:500',
            'account_title' => 'nullable|string|max:500',
            'payment_type' => 'nullable|string|max:500',
            'payment_account' => 'nullable|string|max:500',
            'availabilities' => 'sometimes',
            'division' => 'sometimes|nullable|string|max:255',
        ]);

        if (isset($validated['division'])) {
            $validated['thana'] = $validated['division'];
            unset($validated['division']);
        }



        try {
            DB::beginTransaction();
            if ($request->hasFile('avatar')) {

                // Delete the previous avatar if it exists
                if ($user->doctorProfile->avatar) {
                    // Convert full URL to relative path
                    // $relativePath = str_replace(asset('') . '/', '', $user->doctorProfile->avatar);
                    $relativePath = str_replace(
                        asset(''),
                        '',
                        $user->doctorProfile->avatar
                    );




                    // return response()->json($relativePath);

                    // Check if the file exists and delete it
                    if (File::exists(public_path($relativePath))) {

                        File::delete(public_path($relativePath));
                    }
                }

                // Generate a unique file name for the new avatar
                $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

                // Move the uploaded image to the 'public/images/doctor' directory
                $request->avatar->move(public_path('images/doctor'), $imageName);

                // Store the full URL of the uploaded image
                $validated['avatar'] = asset('images/doctor/' . $imageName); // Add avatar URL to the validated data
            }
            // Update the user's attributes
            // if (isset($validated['phone'])) {
            //     $user->phone = $validated['phone'];
            // }

            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }

            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }

            $user->save();

            // Update or create the doctor profile
            $user->doctorProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $validated
            );

            if ($request->has('availabilities')) {
                $this->updateProviderAvailability($user, $request);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating profile: ' . $e->getMessage()], 500);
        }



        return response()->json(['message' => 'Profile updated successfully.']);
    }

    public function updateLawyer(Request $request, $uniqueUserId)
    {
        $user = User::where('unique_user_id', $uniqueUserId)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Validate the incoming request
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'lawyer_title_id' => 'sometimes|required|exists:lawyer_titles,id',
            'bio' => 'nullable|string',
            'pricing' => 'nullable|numeric|min:0',

            'district' => 'nullable|string|max:255',
            'thana' => 'nullable|string|max:255',
            'practice_area' => 'nullable|string|max:255',
            'identification_no' => 'sometimes|required|string|max:255',
            'bar_registration_no' => 'sometimes|required|string|max:255',
            'active_from' => 'nullable|date_format:H:i',
            'active_to' => 'nullable|date_format:H:i|after:active_from',
            'address' => 'nullable|string|max:500',
            'avatar' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'bank_name' => 'nullable|string|max:500',
            'account_title' => 'nullable|string|max:500',
            'payment_type' => 'nullable|string|max:500',
            'payment_account' => 'nullable|string|max:500',
            'availabilities' => 'sometimes',
            'division' => 'sometimes|nullable|string|max:255',
            'gender' =>   'sometimes|nullable|string|max:255',
            'dob' => 'sometimes|nullable|date|before:today',

        ]);

        // Update the user's attributes
        // if (isset($validated['phone'])) {
        //     $user->phone = $validated['phone'];
        // }

        if (isset($validated['division'])) {
            $validated['thana'] = $validated['division'];
            unset($validated['division']);
        }


        try {
            DB::beginTransaction();
            if ($request->hasFile('avatar')) {

                // Delete the previous avatar if it exists
                if ($user->lawyerProfile->avatar) {
                    // Convert full URL to relative path
                    // $relativePath = str_replace(asset('') . '/', '', $user->lawyerProfile->avatar);
                    $relativePath = str_replace(
                        asset(''),
                        '',
                        $user->lawyerProfile->avatar
                    );




                    // return response()->json($relativePath);

                    // Check if the file exists and delete it
                    if (File::exists(public_path($relativePath))) {

                        File::delete(public_path($relativePath));
                    }
                }

                // Generate a unique file name for the new avatar
                $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

                // Move the uploaded image to the 'public/images/lawyer' directory
                $request->avatar->move(public_path('images/lawyer'), $imageName);

                // Store the full URL of the uploaded image
                $validated['avatar'] = asset('images/lawyer/' . $imageName); // Add avatar URL to the validated data
            }

            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }

            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }

            $user->save();

            // Update or create the customer profile
            $user->lawyerProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $validated
            );
            if ($request->has('availabilities')) {
                $this->updateProviderAvailability($user, $request);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating profile: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    public function commonUpdate(Request $request, $uniqueUserId)
    {
        $user = User::where('unique_user_id', $uniqueUserId)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Validate the incoming request
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',

            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'bio' => 'nullable|string',
            'pricing' => 'nullable|numeric|min:0',

            'district' => 'nullable|string|max:255',
            'thana' => 'nullable|string|max:255',
            'identification_no' => 'sometimes|required|string|max:255',
            'active_from' => 'nullable|date_format:H:i',
            'active_to' => 'nullable|date_format:H:i|after:active_from',
            'unique_identification_no' => 'sometimes|required|string|max:255',
            'other_data' => 'nullable',  // Optional other data field (JSON or text)
            'address' => 'nullable|string|max:500',
            'avatar' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'bank_name' => 'nullable|string|max:500',
            'account_title' => 'nullable|string|max:500',
            'payment_type' => 'nullable|string|max:500',
            'payment_account' => 'nullable|string|max:500',
            'availabilities' => 'sometimes',
            'division' => 'sometimes|nullable|string|max:255',
            'gender' =>   'sometimes|nullable|string|max:255',
            'dob' => 'sometimes|nullable|date|before:today',
        ]);

        // Update the user's basic details (skip password update if not provided)
        // if (isset($validated['phone'])) {
        //     $user->phone = $validated['phone'];
        // }
        if (isset($validated['division'])) {
            $validated['thana'] = $validated['division'];
            unset($validated['division']);
        }


        try {
            DB::beginTransaction();
            if ($request->hasFile('avatar')) {

                // Delete the previous avatar if it exists
                if ($user->commonProfile->avatar) {
                    // Convert full URL to relative path
                    // $relativePath = str_replace(asset('') . '/', '', $user->commonProfile->avatar);
                    $relativePath = str_replace(
                        asset(''),
                        '',
                        $user->commonProfile->avatar
                    );




                    // return response()->json($relativePath);

                    // Check if the file exists and delete it
                    if (File::exists(public_path($relativePath))) {

                        File::delete(public_path($relativePath));
                    }
                }

                // Generate a unique file name for the new avatar
                $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

                // Move the uploaded image to the 'public/images/common' directory
                $request->avatar->move(public_path('images/{$user->role->name}'), $imageName);

                // Store the full URL of the uploaded image
                $validated['avatar'] = asset('images/{$user->role->name}/' . $imageName); // Add avatar URL to the validated data
            }


            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }

            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }

            $user->save();

            // Use null coalescing to handle missing fields for common profile
            $bio = $validated['bio'] ?? $user->commonProfile->bio;
            $pricing = $validated['pricing'] ?? $user->commonProfile->pricing;
            $gender = $validated['gender'] ?? $user->commonProfile->gender;
            $dob = $validated['dob'] ?? $user->commonProfile->dob;
            $district = $validated['district'] ?? $user->commonProfile->district;
            $thana = $validated['thana'] ?? $user->commonProfile->thana;
            $identification_no = $validated['identification_no'] ?? $user->commonProfile->identification_no;
            $active_from = $validated['active_from'] ?? $user->commonProfile->active_from;
            $active_to = $validated['active_to'] ?? $user->commonProfile->active_to;
            $unique_identification_no = $validated['unique_identification_no'] ?? $user->commonProfile->uniqueIdentification->unique_identification_no;
            $address = $validated['address'] ?? $user->commonProfile->address;
            $avatar = $validated['avatar'] ?? $user->commonProfile->avatar;
            $bank_name = $validated['bank_name'] ?? $user->commonProfile->bank_name;
            $account_title = $validated['account_title'] ?? $user->commonProfile->account_title;
            $payment_type = $validated['payment_type'] ?? $user->commonProfile->payment_type;
            $payment_account = $validated['payment_account'] ?? $user->commonProfile->payment_account;




            // Process `other_data` to ensure it's in JSON format
            $otherData = $validated['other_data'] ?? $user->commonProfile->uniqueIdentification->other_data;

            if ($otherData) {
                // If `other_data` is an array or object, convert it to JSON
                if (is_array($otherData) || is_object($otherData)) {
                    $otherData = json_encode($otherData);
                }

                // Check if it's a valid JSON string
                if (json_decode($otherData) === null && json_last_error() !== JSON_ERROR_NONE) {
                    // If it's not valid JSON, wrap it in a JSON object with the 'data' key
                    $otherData = json_encode(['data' => $otherData]);
                }
            }

            // Update the common profile for the user
            $commonProfile = $user->commonProfile;  // Get the related common profile
            if ($commonProfile) {
                $commonProfile->update([
                    'bio' => $bio,
                    'pricing' => $pricing,
                    'gender' => $gender,
                    'dob' => $dob,
                    'district' => $district,
                    'thana' => $thana,
                    'identification_no' => $identification_no,
                    'active_from' => $active_from,
                    'active_to' => $active_to,
                    'address' => $address,
                    'avatar' => $avatar,
                    'bank_name' => $bank_name,
                    'account_title' => $account_title,
                    'payment_type' => $payment_type,
                    'payment_account' => $payment_account,
                ]);
            }

            // Update the unique identification record for the user
            $uniqueIdentification = $commonProfile->uniqueIdentification;  // Get the related unique identification
            if ($uniqueIdentification) {
                $uniqueIdentification->update([
                    'unique_identification_no' => $unique_identification_no,
                    'other_data' => $otherData,  // Store JSON (either valid or encoded)
                ]);
            }

            if ($request->has('availabilities')) {
                $this->updateProviderAvailability($user, $request);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating profile: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Profile updated successfully.']);
    }


    private function updateProviderAvailability(User $user, Request $request)
    {
        // Decode the JSON string
        $availabilities = json_decode($request->input('availabilities'), true);

        // Validate the decoded availabilities array
        $validator = Validator::make(
            ['availabilities' => $availabilities], // Wrap the availabilities into an array for validation
            [
                'availabilities' => 'required|array',
                'availabilities.*.availability_type' => 'required|in:appointment,instant_consultation',
                'availabilities.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'availabilities.*.time_slots' => 'required|array|min:1',
                'availabilities.*.time_slots.*.start_time' => 'required|date_format:H:i',
                'availabilities.*.time_slots.*.end_time' => 'required|date_format:H:i|after:availabilities.*.time_slots.*.start_time',
            ],
            [
                // Custom messages
                'availabilities.required' => 'Availabilities field is required.',
                'availabilities.array' => 'Availabilities should be an array.',
                'availabilities.*.availability_type.required' => 'Each availability block must have an availability type.',
                'availabilities.*.availability_type.in' => 'Availability type must be either appointment or instant_consultation.',
                'availabilities.*.day.required' => 'Each availability block must have a day.',
                'availabilities.*.day.in' => 'Day must be one of the following: monday, tuesday, wednesday, thursday, friday, saturday, sunday.',
                'availabilities.*.time_slots.required' => 'Each availability block must include a time_slots field.',
                'availabilities.*.time_slots.array' => 'The time_slots field must be an array.',
                'availabilities.*.time_slots.min' => 'Each availability block must contain at least one time slot.',
                'availabilities.*.time_slots.*.start_time.required' => 'Start time is required for each time slot.',
                'availabilities.*.time_slots.*.start_time.date_format' => 'Start time must be in the format H:i.',
                'availabilities.*.time_slots.*.end_time.required' => 'End time is required for each time slot.',
                'availabilities.*.time_slots.*.end_time.date_format' => 'End time must be in the format H:i.',
                'availabilities.*.time_slots.*.end_time.after' => 'End time must be after the start time.',
            ]
        );


        if ($validator->fails()) {


            throw new ValidationException($validator);
        }
        ServiceProviderAvailability::where('provider_id', $user->id)->delete();

        foreach ($availabilities as $availabilityBlock) {
            foreach ($availabilityBlock['time_slots'] as $slot) {

                $carbon = Carbon::now(env('PROVIDER_TIMEZONE', 'Asia/Dhaka'));
                $todayDate = $carbon->format('Y-m-d');
                $startTimeInDhaka = Carbon::createFromFormat('Y-m-d H:i', $todayDate . ' ' . $slot['start_time'], env('PROVIDER_TIMEZONE', 'Asia/Dhaka'));
                $endTimeInDhaka = Carbon::createFromFormat('Y-m-d H:i', $todayDate . ' ' . $slot['end_time'], env('PROVIDER_TIMEZONE', 'Asia/Dhaka'));

                $startTimeInUTC = $startTimeInDhaka->copy()->setTimezone(env('CUSTOMER_TIMEZONE', 'UTC'));
                $endTimeInUTC = $endTimeInDhaka->copy()->setTimezone(env('CUSTOMER_TIMEZONE', 'UTC'));

                $startDay = strtolower($availabilityBlock['day']);
                $endDay = $startDay;


                if ($startTimeInUTC->toDateString() < $startTimeInDhaka->toDateString()) {
                    $startDay = $this->getPreviousDay($startDay);
                }


                if ($endTimeInUTC->toDateString() < $endTimeInDhaka->toDateString()) {
                    $endDay = $this->getPreviousDay($endDay);
                }


                if ($startDay !== $endDay) {

                    ServiceProviderAvailability::create([
                        'provider_id' => $user->id,
                        'availability_type' => $availabilityBlock['availability_type'],
                        'day' => $startDay,
                        'start_time' => $startTimeInUTC->toTimeString(),
                        'end_time' => $startTimeInUTC->copy()->endOfDay()->toTimeString(),
                        'slot_duration' => $availabilityBlock['slot_duration'] ?? env('SLOT_DURATION', 60),
                    ]);


                    ServiceProviderAvailability::create([
                        'provider_id' => $user->id,
                        'availability_type' => $availabilityBlock['availability_type'],
                        'day' => $endDay,
                        'start_time' => $endTimeInUTC->copy()->startOfDay()->toTimeString(),
                        'end_time' => $endTimeInUTC->toTimeString(),
                        'slot_duration' => $availabilityBlock['slot_duration'] ?? env('SLOT_DURATION', 60),
                    ]);
                } else {

                    ServiceProviderAvailability::create([
                        'provider_id' => $user->id,
                        'availability_type' => $availabilityBlock['availability_type'],
                        'day' => $startDay,
                        'start_time' => $startTimeInUTC->toTimeString(),
                        'end_time' => $endTimeInUTC->toTimeString(),
                        'slot_duration' => $availabilityBlock['slot_duration'] ?? env('SLOT_DURATION', 60),
                    ]);
                }
            }
        }

        // return response()->json(['message' => 'Availabilities stored successfully']);

    }


    public function getPreviousDay($currentDay)
    {
        $days = [
            'monday' => 'sunday',
            'tuesday' => 'monday',
            'wednesday' => 'tuesday',
            'thursday' => 'wednesday',
            'friday' => 'thursday',
            'saturday' => 'friday',
            'sunday' => 'saturday',
        ];

        return $days[$currentDay];
    }
}
