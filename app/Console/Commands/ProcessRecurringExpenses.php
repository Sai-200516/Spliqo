<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\Group;
use App\Services\BalanceService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessRecurringExpenses extends Command
{
    protected $signature = 'expenses:process-recurring';
    protected $description = 'Create new expense entries for due recurring expenses';

    public function __construct(
        private BalanceService $balances,
        private NotificationService $notifications
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $today = Carbon::today();

        $templates = Expense::whereNotNull('recurrence')
            ->where('recurrence.active', true)
            ->where('recurrence.next_due', '<=', $today->toDateString())
            ->get();

        foreach ($templates as $template) {
            $recurrence = $template->recurrence;
            $group      = Group::find($template->group_id);

            if (!$group) {
                continue;
            }

            // Clone the expense as a new document
            $newExpense = Expense::create([
                'title'       => $template->title,
                'amount'      => $template->amount,
                'currency'    => $template->currency,
                'category'    => $template->category,
                'group_id'    => $template->group_id,
                'split_type'  => $template->split_type,
                'splits'      => collect($template->splits ?? [])->map(function ($s) {
                    return array_merge($s, ['is_settled' => false]);
                })->toArray(),
                'paid_by'     => $template->paid_by,
                'notes'       => $template->notes,
                'tags'        => $template->tags ?? [],
                'attachments' => [],
                'created_by'  => $template->created_by,
                'recurrence'  => null, // clones are not templates
            ]);

            // Recalculate balances
            $this->balances->recalculate($template->group_id);

            // Notify group members
            $memberIds = collect($group->members)->pluck('user_id')->toArray();
            $this->notifications->notifyGroupMembers(
                $memberIds,
                $template->created_by,
                'expense_added',
                'Recurring expense added',
                '"' . $newExpense->title . '" was automatically added — ' . $newExpense->amount_formatted,
                ['group_id' => (string) $template->group_id, 'expense_id' => (string) $newExpense->_id]
            );

            // Advance next_due date
            $nextDue = Carbon::parse($recurrence['next_due']);
            $nextDue = match ($recurrence['frequency']) {
                'weekly'    => $nextDue->addWeek(),
                'biweekly'  => $nextDue->addWeeks(2),
                'monthly'   => $nextDue->addMonth(),
                'quarterly' => $nextDue->addMonths(3),
                'yearly'    => $nextDue->addYear(),
                default     => null,
            };

            if ($nextDue) {
                $template->update([
                    'recurrence' => array_merge($recurrence, ['next_due' => $nextDue->toDateString()]),
                ]);
            } else {
                $template->update([
                    'recurrence' => array_merge($recurrence, ['active' => false]),
                ]);
            }

            $this->line("Created recurring: {$newExpense->title} (group {$template->group_id})");
        }

        $this->info("Processed {$templates->count()} recurring expense(s).");
    }
}
