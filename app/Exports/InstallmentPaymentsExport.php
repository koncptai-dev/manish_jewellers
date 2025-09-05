<?php

namespace App\Exports;

use App\Models\InstallmentPaymentDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InstallmentPaymentsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $searchValue;
    protected $date;

    public function __construct($searchValue = null, $date = null)
    {
        $this->searchValue = $searchValue;
        $this->date = $date;
    }

    public function collection()
    {
        return InstallmentPaymentDetail::with(['installmentPayment.user'])
            ->when($this->searchValue, function($query) {
                $searchValue = $this->searchValue;
                $query->where(function ($q) use ($searchValue) {
                    $q->whereHas('installmentPayment.user', function ($userQuery) use ($searchValue) {
                        $userQuery->where('name', 'like', '%' . $searchValue . '%');
                    })
                    ->orWhere('transaction_ref', 'like', '%' . $searchValue . '%');
                });
            })
            ->when($this->date, function($query) {
                $query->whereDate('created_at', $this->date);
            })
            ->where('payment_by', 'User')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'User Name',
            'Plan Code',
            'Plan Category',
            'Payment ID',
            'Paid Amount',
            'Plan Status',
            'Date',
        ];
    }

    public function map($transaction): array
    {
        return [
            optional(optional($transaction->installmentPayment)->user)->name,
            optional($transaction->installmentPayment)->plan_code,
            optional($transaction->installmentPayment)->plan_category,
            $transaction->transaction_ref,
            $transaction->monthly_payment,
            ucfirst($transaction->payment_status),
            $transaction->created_at->format('d-m-Y H:i'),
        ];
    }
}
