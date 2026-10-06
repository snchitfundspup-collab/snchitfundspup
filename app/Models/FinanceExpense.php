<?php

namespace App\Models;

/**
 * Sri Lakshmi Micro Finance's own spending: the same as a chit fund
 * expense, kept in its own table.
 */
class FinanceExpense extends Expense
{
    protected $table = 'finance_expenses';
}
