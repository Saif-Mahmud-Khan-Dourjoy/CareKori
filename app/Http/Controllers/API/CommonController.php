<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return response()->json([

            'getters'    => $getters,
            'providers'  => $providers,
            'moderators' => $moderators,
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



        return response()->json([
            'services_count' => $servicesCount,
            'used_services_count' => $usedServicesCount,
            'sub_services_count' => $totalSubservicesCatalog,
            'used_sub_services_count' => $totalSubservicesUsed,
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
}
