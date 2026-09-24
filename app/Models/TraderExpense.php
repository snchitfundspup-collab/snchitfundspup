<?php

namespace App\Models;

/**
 * SN Traders' own spending: the same as a chit fund expense, kept in its
 * own table.
 */
class TraderExpense extends Expense
{
    protected $table = 'trader_expenses';
}
