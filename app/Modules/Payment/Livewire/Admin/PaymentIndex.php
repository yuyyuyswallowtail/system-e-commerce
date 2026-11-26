<?php

declare(strict_types=1);

namespace App\Modules\Payment\Livewire\Admin;

use App\Models\User;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Payments')]
class PaymentIndex extends Component
{
    use WithPagination;

    public string $status = '';

    /** @var array<string, string> */
    public array $reasons = [];

    public function mount(): void
    {
        abort_unless($this->actor()->can('payments.read'), 403);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function refund(string $paymentUuid): void
    {
        $actor = $this->actor();
        abort_unless($actor->can('payments.read'), 403);
        abort_unless(Str::isUuid($paymentUuid), 404);
        $payment = Payment::query()->where('uuid', $paymentUuid)->firstOrFail();
        abort_unless(Gate::forUser($actor)->allows('refund', $payment), 403);
        $this->resetErrorBag();

        try {
            app(PaymentService::class)->refund($paymentUuid, trim((string) ($this->reasons[$paymentUuid] ?? '')), $actor);
            unset($this->reasons[$paymentUuid]);
        } catch (DomainValidationException $exception) {
            foreach ($exception->fields() as $messages) {
                $this->addError('refund', (string) (is_array($messages) ? $messages[0] : $messages));
            }
        } catch (ConflictException $exception) {
            $this->addError('refund', $exception->getMessage());
        } catch (NotFoundException) {
            abort(404);
        }
    }

    public function render(): View
    {
        $actor = $this->actor();
        abort_unless($actor->can('payments.read'), 403);
        $statuses = (array) config('shop.payment_statuses');
        $query = Payment::query()->orderByDesc('created_at')->orderByDesc('id');

        if ($this->status !== '' && in_array($this->status, $statuses, true)) {
            $query->where('status', $this->status);
        }

        return view('payment.admin.payments', [
            'payments' => $query->paginate((int) config('shop.pagination.default')),
            'statuses' => $statuses,
            'canRefund' => $actor->can('payments.refund'),
            'paidStatus' => (string) config('shop.payment.statuses.paid'),
        ]);
    }

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
