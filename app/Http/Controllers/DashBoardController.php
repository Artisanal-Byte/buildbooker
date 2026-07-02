<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashBoardController extends Controller
{
    public function analytics()
    {
        $user = Auth::user();
        $organisation = Organisation::find($user->organisation_id);

        $organisation->load('projects.units.transactions');
        $totalProjects = $organisation->projects->count();
        // dd($test);

        $totalUnsoldUnits = $organisation->projects->sum("total_units") - $organisation->projects->sum(function ($project) {
            return $project->units->where('is_sold', true)->count();
        });

        $totalOutStandingAmmount = 0;
        $projects = [];

        foreach ($organisation->projects as $project) {
            $paidAmountOfunits = 0;
            $totalUnitAmount = 0;

            foreach ($project->units as $unit) {
                if ($unit->total_amount) {
                    $paidAmountOfunits += (float) $unit->transactions
                        ->whereNull('deleted_at')
                        ->sum('transaction_amount');
                    // dd($paidAmountOfunits, $unit->toArray());
                    $totalUnitAmount += (float) ($unit->total_amount ?? 0);
                }
            }
            $totalPendingAmmountOfUnits = $totalUnitAmount - $paidAmountOfunits;

            $projects[] = [
                'id' => $project->id,
                'organisation_id' => $user->organisation_id,
                'name' => $project->name,
                'project_logo' => asset($project->logo),
                'total_units' => $project->total_units,
                'totalUnitAmount' => $totalUnitAmount,
                'totalPendingAmountOfProject' => $totalPendingAmmountOfUnits,
            ];

            $totalOutStandingAmmount += $totalPendingAmmountOfUnits;
        }

        return Inertia::render('Dashboard', [
            'totalProjects' => $totalProjects,
            'totalOutStandingAmmount' => $totalOutStandingAmmount,
            'totalUnsoldUnits' => $totalUnsoldUnits,
            'projects' => $projects,
        ]);
    }
}
