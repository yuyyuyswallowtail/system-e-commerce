<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Livewire\Admin;

use App\Models\User;
use App\Modules\Dashboard\Services\DashboardService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    private const PERMISSION = 'dashboard.read';

    public function mount(): void
    {
        $this->allow();
    }

    public function refresh(DashboardService $dashboard): void
    {
        $this->allow();
        $dashboard->refresh();
    }

    public function render(DashboardService $dashboard): View
    {
        $this->allow();

        $result = $dashboard->forBlock((string) config('shop.dashboard.summary_block'));
        $data = $result['data'];

        $boxes = [];
        $byStatus = [];

        if ($data !== null) {
            $orders = $data['orders'] ?? null;
            $payments = $data['payments'] ?? null;
            $inventory = $data['inventory'] ?? null;
            $catalog = $data['catalog'] ?? null;
            $content = $data['content'] ?? null;
            $comments = $data['comments'] ?? null;
            $paidStatus = (string) config('shop.payment.statuses.paid');

            if ($orders !== null) {
                $boxes[] = ['label' => 'Orders total', 'value' => (string) $orders['total'], 'icon' => 'fas fa-clipboard-list', 'color' => 'info'];
                $boxes[] = ['label' => 'Revenue', 'value' => Money::format((int) $orders['revenue_amount'], (string) $orders['currency']), 'icon' => 'fas fa-coins', 'color' => 'success'];
                $byStatus = (array) $orders['by_status'];
            }

            if ($payments !== null) {
                $boxes[] = [
                    'label' => 'Paid payments',
                    'value' => (string) ($payments['by_status'][$paidStatus] ?? 0),
                    'note' => Money::format((int) $payments['paid_amount'], (string) $payments['currency']),
                    'icon' => 'fas fa-credit-card',
                    'color' => 'success',
                ];
            }

            if ($inventory !== null) {
                $boxes[] = ['label' => 'Low stock', 'value' => (string) $inventory['low_stock'], 'icon' => 'fas fa-exclamation-triangle', 'color' => 'warning'];
                $boxes[] = ['label' => 'Out of stock', 'value' => (string) $inventory['out_of_stock'], 'icon' => 'fas fa-box-open', 'color' => 'danger'];
            }

            if ($catalog !== null) {
                $boxes[] = ['label' => 'Active products', 'value' => (string) $catalog['active'], 'icon' => 'fas fa-box', 'color' => 'primary'];
            }

            if ($content !== null) {
                $boxes[] = ['label' => 'Published articles', 'value' => (string) $content['published'], 'icon' => 'fas fa-newspaper', 'color' => 'secondary'];
            }

            if ($comments !== null) {
                $boxes[] = ['label' => 'Visible comments', 'value' => (string) $comments['visible'], 'icon' => 'fas fa-comments', 'color' => 'teal'];
            }
        }

        return view('dashboard.admin.dashboard', [
            'unavailable' => $data === null,
            'warnings' => $result['warnings'],
            'boxes' => $boxes,
            'byStatus' => $byStatus,
        ]);
    }

    private function allow(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->can(self::PERMISSION), 403);

        return $user;
    }
}
